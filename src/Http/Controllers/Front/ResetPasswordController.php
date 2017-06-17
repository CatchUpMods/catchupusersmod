<?php namespace WebEd\Base\Users\Http\Controllers\Front;

use WebEd\Base\Http\Controllers\BaseFrontController;
use WebEd\Base\Users\Http\Requests\ForgotPasswordRequest;
use WebEd\Base\Users\Http\Requests\ResetPasswordRequest;
use WebEd\Base\Users\Repositories\Contracts\PasswordResetRepositoryContract;
use WebEd\Base\Users\Repositories\Contracts\UserRepositoryContract;
use WebEd\Base\Users\Repositories\UserRepository;
use WebEd\Base\Users\ServerActions\ForgotPasswordServerAction;
use WebEd\Base\Users\ServerActions\ResetPasswordServerAction;

class ResetPasswordController extends BaseFrontController
{
    /**
     * @var PasswordResetRepositoryContract
     */
    protected $passwordResetRepository;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    public function __construct(
        PasswordResetRepositoryContract $passwordResetRepository,
        UserRepositoryContract $userRepository
    )
    {
        parent::__construct();

        $this->passwordResetRepository = $passwordResetRepository;

        $this->userRepository = $userRepository;
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function getIndex()
    {
        $passwordReset = $this->passwordResetRepository
            ->getPasswordResetByToken($this->request->get('token'));

        if (!$passwordReset) {
            return $this->error('User not exists');
        }

        $user = $this->userRepository
            ->findWhere([
                'email' => $passwordReset->email,
            ]);

        if (!$user) {
            return $this->error('User not exists');
        }

        $this->setBodyClass('reset-password-page');
        $this->setPageTitle(trans('webed-users::auth.reset_password'));

        return $this->view(config('webed-auth.front_actions.reset_password.view') ?: 'webed-users::front.auth.reset-password');
    }

    /**
     * @param ResetPasswordRequest $request
     * @param ResetPasswordServerAction $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postIndex(ResetPasswordRequest $request, ResetPasswordServerAction $action)
    {
        $result = $action->run($request->get('token'), $request->get('password'));

        flash_messages()
            ->addMessages($result['messages'], $result['error'] ? 'error' : 'success')
            ->showMessagesOnSession();

        if ($result['error']) {
            return redirect()->back();
        }

        if (config('webed-auth.front_actions.reset_password.auto_sign_in_after_reset')) {
            auth()->loginUsingId(
                $result['data']['user_id'],
                config('webed-auth.front_actions.reset_password.remember_login')
            );
        }

        return redirect()->to(route('front.web.resolve-pages.get'));
    }

    /**
     * @param array|string $msg
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function error($msg)
    {
        flash_messages()
            ->addMessages($msg, 'error')
            ->showMessagesOnSession();

        return redirect()->to(route('front.web.resolve-pages.get'));
    }
}
