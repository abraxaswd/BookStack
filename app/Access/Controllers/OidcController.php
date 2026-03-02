<?php

namespace BookStack\Access\Controllers;

use BookStack\Access\Oidc\OidcException;
use BookStack\Access\Oidc\OidcService;
use BookStack\Exceptions\OidcEmailRequestException;
use BookStack\Http\Controller;
use Illuminate\Http\Request;

class OidcController extends Controller
{
    public function __construct(
        protected OidcService $oidcService
    ) {
        $this->middleware('guard:oidc');
    }

    /**
     * Start the authorization login flow via OIDC.
     */
    public function login()
    {
        try {
            $loginDetails = $this->oidcService->login();
        } catch (OidcException $exception) {
            $this->showErrorNotification($exception->getMessage());

            return redirect('/login');
        }

        session()->put('oidc_state', time() . ':' . $loginDetails['state']);

        return redirect($loginDetails['url']);
    }

    /**
     * Authorization flow redirect callback.
     * Processes authorization response from the OIDC Authorization Server.
     */
    public function callback(Request $request)
    {
        $responseState = $request->query('state');
        $splitState =  explode(':', session()->pull('oidc_state', ':'), 2);
        if (count($splitState) !== 2) {
            $splitState = [null, null];
        }

        [$storedStateTime, $storedState] = $splitState;
        $threeMinutesAgo = time() - 3 * 60;

        if (!$storedState || $storedState !== $responseState || intval($storedStateTime) < $threeMinutesAgo) {
            $this->showErrorNotification(trans('errors.oidc_fail_authed', ['system' => config('oidc.name')]));

            return redirect('/login');
        }

        try {
            $this->oidcService->processAuthorizeResponse($request->query('code'));
        } catch (OidcEmailRequestException) {
            return redirect('/oidc/email');
        } catch (OidcException $oidcException) {
            $this->showErrorNotification($oidcException->getMessage());

            return redirect('/login');
        }

        return redirect()->intended();
    }

    /**
     * Show the form to request an email address from the user
     * when the OIDC provider did not supply one.
     */
    public function showEmailForm()
    {
        if (!session()->has('oidc_pending_user_details')) {
            return redirect('/login');
        }

        return view('auth.oidc-email');
    }

    /**
     * Handle the submitted email address for a pending OIDC login.
     */
    public function submitEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $this->oidcService->completeLoginWithEmail($request->get('email'));
        } catch (OidcException $exception) {
            $this->showErrorNotification($exception->getMessage());

            return redirect('/login');
        }

        return redirect()->intended();
    }

    /**
     * Log the user out, then start the OIDC RP-initiated logout process.
     */
    public function logout()
    {
        return redirect($this->oidcService->logout());
    }
}
