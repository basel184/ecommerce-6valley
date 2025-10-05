<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Enums\SessionKey;
use App\Enums\UserRole;
use App\Enums\ViewPaths\Admin\Auth;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\LoginRequest;
use App\Models\Admin;
use App\Services\AdminService;
// use App\Traits\RecaptchaTrait; // Removed
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class LoginController extends BaseController
{
    // use RecaptchaTrait; // Removed

    public function __construct(private readonly Admin $admin, private readonly AdminService $adminService)
    {
        $this->middleware('guest:admin', ['except' => ['logout']]);
    }

    public function index(?Request $request, string $type = null): View|Collection|LengthAwarePaginator|null|callable
    {
        return $this->getLoginView(loginUrl: $type);
    }

    // generateReCaptcha function removed

    private function getLoginView(string $loginUrl): View
    {
        $loginTypes = [
            UserRole::ADMIN => getWebConfig(name: 'admin_login_url'),
            UserRole::EMPLOYEE => getWebConfig(name: 'employee_login_url')
        ];

        $userType = array_search($loginUrl, $loginTypes);
        abort_if(!$userType, 404);

        // reCAPTCHA completely disabled
        $recaptchaBuilder = null;
        $recaptcha = ['status' => 0]; // Force disable recaptcha

        return view('admin-views.auth.login', compact('recaptchaBuilder', 'recaptcha'))->with(['role' => $userType]);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        // CAPTCHA validation completely disabled
        // $recaptcha = getWebConfig(name: 'recaptcha');
        // if (isset($recaptcha) && $recaptcha['status'] == 1) {
        //     $request->validate([
        //         'g-recaptcha-response' => [
        //             function ($attribute, $value, $fail) {
        //                 $secretKey = getWebConfig(name: 'recaptcha')['secret_key'];
        //                 $response = $value;
        //                 $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secretKey . '&response=' . $response;
        //                 $response = Http::get($url);
        //                 $response = $response->json();
        //                 if (!isset($response['success']) || !$response['success']) {
        //                     $fail(translate('ReCAPTCHA_Failed'));
        //                 }
        //             },
        //         ],
        //     ]);
        // } else if (strtolower(session(SessionKey::ADMIN_RECAPTCHA_KEY)) != strtolower($request['default_captcha_value'])) {
        //     ToastMagic::error(translate('ReCAPTCHA_Failed'));
        //     return back();
        // }

        $admin = $this->admin->where('email', $request['email'])->first();

        if (isset($admin) && in_array($request['role'], [UserRole::ADMIN, UserRole::EMPLOYEE]) && $admin->status) {
            if ($admin['id'] == 1 && $request['role'] != 'admin') {
                return redirect()->back()->withInput($request->only('email', 'remember'))
                    ->withErrors([translate('Please_login_from_the_admin_login_page')]);
            }
            if ($admin['id'] != 1 && $request['role'] != 'employee') {
                return redirect()->back()
                    ->withInput($request->only('email', 'remember'))
                    ->withErrors([translate('Please_login_from_the_employee_login_page')]);
            }
            if ($this->adminService->isLoginSuccessful($request['email'], $request['password'], $request['remember'])) {
                return redirect()->route('admin.dashboard.index');
            }
        }

        return redirect()->back()->withInput($request->only('email', 'remember'))
            ->withErrors([translate('credentials_does_not_match_or_your_account_has_been_suspended')]);
    }

    public function logout(): RedirectResponse
    {
        $authType = auth('admin')->id() == 1 ? 'admin' : 'employee';
        $this->adminService->logout();
        session()->flash('success', translate('logged out successfully'));
        if ($authType == 'employee') {
            return redirect('login/' . getWebConfig(name: 'employee_login_url'));
        } else {
            return redirect('login/' . getWebConfig(name: 'admin_login_url'));
        }
    }

}
