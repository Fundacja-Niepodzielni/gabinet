<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Tozsamosc\KontaOidc;
use App\Tozsamosc\RejestrSesji;
use App\Tozsamosc\SladWylogowania;
use App\Tozsamosc\WalidatorTokenu;
use App\Wsparcie\Typy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Back-channel logout (OIDC Back-Channel Logout 1.0), kontrakt §4.5.
 *
 * Żądanie przychodzi od SERWERA IdP: bez ciasteczka, bez sesji, bez CSRF.
 * To jest powód, dla którego endpoint musi mieć zdjęty CSRF — i jednocześnie
 * powód, dla którego walidacja logout tokenu musi być kompletna. Bez niej
 * endpoint jest „wyłącznikiem sesji dla każdego, kto zna adres".
 */
final class BackchannelLogoutController extends Controller
{
    public function __construct(private readonly KontaOidc $oidc) {}

    public function przyjmij(Request $request): JsonResponse
    {
        $logoutToken = Typy::napis($request->input('logout_token'));

        // ŚLAD WEJŚCIA — zapisywany PIERWSZY, przed czymkolwiek, co może rzucić.
        //
        // Klauzula fail-safe logout (standard B8, znalezisko sesji `konta`):
        // asercja musi ODRÓŻNIAĆ „token nie dotarł" od „dotarł i handler padł".
        // Licznik skasowanych sesji myli te dwa stany — oba dają zero. Dlatego
        // znacznik wejścia jest osobnym sygnałem i powstaje zanim wykonamy
        // jakąkolwiek operację sieciową.
        SladWylogowania::wejscie();

        try {
            $wynik = WalidatorTokenu::sprawdz($logoutToken, [
                'issuer' => $this->oidc->issuerPubliczny(),
                // Odświeżenie JWKS pod nieznany `kid` chroni bramka częstotliwości:
                // ten endpoint jest PUBLICZNY i nieuwierzytelniony, więc bez niej
                // każdy token z losowym `kid` byłby żądaniem do Kont Niepodzielni.
                'jwks' => $this->oidc->jwksDlaKid(WalidatorTokenu::kidNiezweryfikowany($logoutToken)),
                // Logout token ma `aud` == client_id (nie wymagana audiencja API).
                'audience' => $this->oidc->clientId(),
                'tolerancja' => $this->oidc->tolerancjaZegara(),
            ]);
        } catch (Throwable $blad) {
            // AWARIA WALIDACJI — sesji NIE kasujemy, ale i nie milczymy.
            //
            // Historia tej gałęzi jest pouczająca. Pierwsza wersja kończyła
            // sesję po `sid` z NIEZWERYFIKOWANEGO tokenu, z uzasadnieniem
            // „skutkiem jest tylko wylogowanie, więc nadmiar bezpieczniejszy
            // niż brak". Pytanie adwersarialne pokazało, że to furtka na
            // WYMUSZONE WYLOGOWANIE: napastnik znający `sid` ofiary mógł ją
            // wyrzucić z systemu, a wyzwalacz wyjątku CZĘŚCIOWO kontrolował,
            // bo `kid` pochodzi z jego własnego tokenu.
            //
            // Naprawa poszła w dwie strony naraz:
            //   1. `KontaOidc::jwksDlaKid()` nie wyrzuca już starych kluczy
            //      przed pobraniem świeżych i nie rzuca przy nieudanym
            //      odświeżeniu — więc niedostępny IdP przestał być awarią,
            //      dopóki mamy klucze w cache'u. Poprawny logout token
            //      przechodzi wtedy ZWYKŁĄ ścieżką i sesja ginie normalnie.
            //   2. Tutaj zostaje przypadek naprawdę beznadziejny: brak kluczy
            //      i brak łączności. Wtedy NIE MA JAK zweryfikować niczego,
            //      więc nie kasujemy — bo jedyne, co moglibyśmy zrobić, to
            //      zaufać napastnikowi.
            //
            // Kod 503 zaprasza IdP do ponowienia (back-channel logout ma
            // ponowienia w specyfikacji), a licznik odmów robi z tego stanu
            // sygnał widoczny dla operatora zamiast ciszy. To jest różnica
            // wobec pierwotnej wady: tam było 500 i sesja żyjąca w milczeniu.
            SladWylogowania::awaria($blad::class);
            SladWylogowania::odmowa();

            Log::error('Back-channel logout: NIE UDAŁO SIĘ zweryfikować tokenu — sesja NIE została zakończona.', [
                'wyjatek' => $blad::class,
            ]);

            return response()
                ->json(['ok' => false, 'awaria' => 'walidacja_niemozliwa', 'skasowane_sesje' => 0], 503)
                ->header('Cache-Control', 'no-store');
        }

        $claims = $wynik['claims'];
        $kontrole = $wynik['kontrole'];

        // Wymagania specyfikacji ponad zwykłą walidację JWT:
        $zdarzenia = $claims['events'] ?? [];
        $kontrole['events'] = is_array($zdarzenia)
            && array_key_exists('http://schemas.openid.net/event/backchannel-logout', $zdarzenia)
                ? 'ok' : 'fail';

        $kontrole['sid_or_sub'] = isset($claims['sid']) || isset($claims['sub']) ? 'ok' : 'fail';

        // `nonce` w logout tokenie jest ZABRONIONY — to zapora przed podstawieniem
        // ID tokenu w miejsce logout tokenu.
        $kontrole['no_nonce'] = array_key_exists('nonce', $claims) ? 'fail' : 'ok';

        $nieudane = array_keys(array_filter($kontrole, static fn (string $v): bool => $v !== 'ok'));

        if ($nieudane !== []) {
            return response()
                ->json(['ok' => false, 'nieudane' => $nieudane, 'kontrole' => $kontrole], 400)
                ->header('Cache-Control', 'no-store');
        }

        $sid = isset($claims['sid']) ? Typy::napis($claims['sid']) : null;
        $skasowane = $sid !== null ? RejestrSesji::zakoncz($sid) : 0;

        return response()
            ->json(['ok' => true, 'skasowane_sesje' => $skasowane])
            ->header('Cache-Control', 'no-store');
    }
}
