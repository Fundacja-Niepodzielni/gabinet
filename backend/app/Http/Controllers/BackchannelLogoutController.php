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
            // FAIL-SAFE. Pobranie JWKS albo discovery sięga po sieć i potrafi
            // rzucić — niedostępny IdP, timeout, błąd 5xx. Bez tej gałęzi
            // handler kończył się kodem 500, a SESJA ŻYŁA DALEJ, w ciszy:
            // dokładnie ten tryb awarii, który back-channel logout ma
            // eliminować. Odmowa wylogowania jest gorsza niż wylogowanie
            // nadmiarowe, więc przy błędzie sesję i tak kończymy.
            //
            // `sid` bierzemy z NIEZWERYFIKOWANEGO tokenu — świadomie. To jedyna
            // informacja, jaką mamy, a skutkiem jest wyłącznie zakończenie
            // sesji: nie nadaje uprawnień i nie ujawnia danych. Ścieżka jest
            // wąska (tylko wyjątek, nie zły podpis), więc nie zamienia się
            // w wygodne narzędzie do wylogowywania cudzych sesji.
            $sidAwaryjny = WalidatorTokenu::sidNiezweryfikowany($logoutToken);
            $skasowane = $sidAwaryjny !== '' ? RejestrSesji::zakoncz($sidAwaryjny) : 0;

            SladWylogowania::awaria($blad::class);

            Log::warning('Back-channel logout: awaria walidacji, sesja zakończona awaryjnie.', [
                'wyjatek' => $blad::class,
                'skasowane_sesje' => $skasowane,
            ]);

            return response()
                ->json(['ok' => false, 'awaria' => 'walidacja', 'skasowane_sesje' => $skasowane], 503)
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
