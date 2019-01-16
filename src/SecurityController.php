<?php






/**
 * Controller used to manage the application security.
 */
class SecurityController extends Controller
{
    public function loginAction(Request $request)
    {
        // Already logged in
        if (self::getUser() !== null) {
            return self::redirectToRoute('home');
        }

        $helper = self::get('security.authentication_utils');
        $last_username = $helper->getLastUsername();
        if (!$last_username) {
            $last_username = $request->get('last_username');
        }

        return self::render('user/login.html.twig', array(
            // last username entered by the user (if any)
            'last_username' => $last_username,
            // last authentication error (if any)
            'error' => $helper->getLastAuthenticationError(),
        ));
    }

    /**
     * This is the route the login form submits to.
     *
     * But, this will never be executed. Symfony will intercept this first
     * and handle the login automatically. See form_login in config/security.yml
     */
    public function loginCheckAction()
    {
        // This should never be reached!
        return self::redirectToRoute('home');
    }

    /**
     * This is the route the user can use to logout.
     *
     * But, this will never be executed. Symfony will intercept this first
     * and handle the logout automatically. See logout in config/security.yml
     *
     * @throws \Exception
     */
    public function logoutAction()
    {
        throw new \Exception('This should never be reached!');
    }
}
