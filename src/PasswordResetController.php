<?php












/**
 * Class PasswordResetController.
 */
class PasswordResetController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * Shows the request new password page
     */
    public function showAction(Request $request)
    {
        // Check if user is already logged in
        if (!is_null(self::getUser())) {
            return self::redirect('/');
        }

        //Creates new PasswordReset entity
        $passwordReset = new PasswordReset();

        //Creates new PasswordResetType Form
        $form = self::createForm(PasswordResetType::class, $passwordReset, array(
            'validation_groups' => array('password_reset'),
        ));

        $form->handleRequest($request);

        //Checks if the form is valid
        if ($form->isValid()) {
            //Creates a reset password-Entity and sends a reset url by Email to the user. if the username and email is correct
            if (self::createResetPasswordEntity($form, $passwordReset)) {
                return self::render('reset_password/confirmation.html.twig', array('email' => $form->get('email')->getData()));
            }
        }
        //Render reset_password twig with the form.
        return self::render('reset_password/reset_password.html.twig', array('form' => $form->createView()));
    }

    /**
     * @param Form $form
     * @param PasswordReset $passwordReset
     *
     * @return bool
     *
     * Creates a resetPassword field in the resetPassword entity, with a reset code, date and the user who want to reset the password.
     * The function sends an email with an url to the user where the user can reset the password
     */
    private function createResetPasswordEntity(Form $form, PasswordReset $passwordReset)
    {

        //Connects with the User Entity
        $repositoryUser = self::getDoctrine()->getRepository('AppBundle:User');

        //Gets the email that is typed in the text-field
        $email = $form->get('email')->getData();

        try {
            //Finds the user based on the email
            $user = $repositoryUser->findUserByEmail($email);
        } catch (NonUniqueResultException $e) {
            $user = null;
        }

        if (is_null($user)) {
            //Error message
            self::get('session')->getFlashBag()->add('errorMessage', '<em>Ingen brukere er registrert med <span class="text-danger">'.$email.'</span></em>');

            return false;
        }

        //Creates a random hex-string as reset code
        $resetCode = bin2hex(openssl_random_pseudo_bytes(12));

        //Hashes the random reset code to store in the database
        $hashedResetCode = hash('sha512', $resetCode, false);

        //creates a DateTime object for the table, this is to have a expiration time for the reset code
        $time = new \DateTime();

        //Delete old resetcodes from the database
        $repositoryPasswordReset = self::getDoctrine()->getRepository('AppBundle:PasswordReset');
        $repositoryPasswordReset->deletePasswordResetsByUser($user);

        //Adds the info in the passwordReset entity
        $passwordReset->setUser($user);
        $passwordReset->setResetTime($time);
        $passwordReset->setHashedResetCode($hashedResetCode);
        $em = self::getDoctrine()->getManager();
        $em->persist($passwordReset);
        $em->flush();

        //Sends a email with the url for resetting the password
        /*
         * @var \Swift_Mime_Message
         */
        $emailMessage = \Swift_Message::newInstance()
            ->setSubject('Tilbakestill passord for kodeklubben.no')
            ->setFrom('ikkesvar@kodeklubben.no')
            ->setTo($email)
            ->setBody(self::renderView('reset_password/new_password_email.txt.twig', array('reseturl' => self::generateUrl('password_new', array('code' => $resetCode), UrlGeneratorInterface::ABSOLUTE_URL))));
        self::get('mailer')->send($emailMessage);

        return true;
    }

    /**
     * @param $code
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * This function resets stores the new password when the user goes to the url for resetting the password
     */
    public function resetPasswordAction($code, Request $request)
    {
        // Check if user is already logged in
        if (!is_null(self::getUser())) {
            return self::redirect('/');
        }

        $repositoryPasswordReset = self::getDoctrine()->getRepository('AppBundle:PasswordReset');

        //Creates a DateTime to know the current time
        $currentTime = new \DateTime();

        //Stores the resetcode that was sent from the url
        $resetCode = $code;
        //hashes the resetcode with the same hash that is stored in the database.
        $hashedResetCode = hash('sha512', $resetCode, false);
        //Retrieves the PasswordReset object with the hashed reset code
        try {
            $passwordReset = $repositoryPasswordReset->findPasswordResetByHashedResetCode($hashedResetCode);
        } catch (NonUniqueResultException $e) {
            $passwordReset = null;
        }

        if (is_null($passwordReset)) {
            //If the resetcode that is provided does not exist in the database, the user is redirected to home
            return self::redirect('/');
        }

        //Finds the user based on the provided reset code.
        $user = $passwordReset->getUser();

        //Creates a new newPasswordType form, and send in user so that it is the password for the correct user that is changed.
        $form = self::createForm(NewPasswordType::class);

        //Handles the request from the form
        $form->handleRequest($request);

        //Finds the time difference from when the resetcode was collected, and now.
        $timeDifference = date_diff($passwordReset->getResetTime(), $currentTime);

        //Checks if the reset code is more than one day old(24 hours)
        if ($timeDifference->d < 1) {
            //checks if the form is valid(the information is stored correctly in the user object)
            if ($form->isValid()) {
                //Deletes the resetcode, so it can only be used one time.
                $repositoryPasswordReset->deletePasswordResetByHashedResetCode($hashedResetCode);
                $plainPassword = $form->get('password')->getData();
                $encoder = self::get('security.password_encoder');
                $hashedPassword = $encoder->encodePassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
                //Updates the database
                $em = self::getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                //renders the login page
                return self::render('user/login.html.twig', array(
                    'last_username' => $user->getEmail(),
                    'error' => null,
                ));
            }
        } //If the reset code is more than 1 day old.
        else {
            //Deletes the resetcode
            $repositoryPasswordReset->deletePasswordResetByHashedResetCode($hashedResetCode);
            //creates a message that states the problem
            $feedback = 'Denne linken er utløpt. Skriv inn E-post for å få tilsendt en ny.';
            //Render the reset_password twig with the message, so the user can get a new reset code.
            return self::render('reset_password/reset_password.html.twig', array('message' => $feedback));
        }

        return self::render('reset_password/new_password.html.twig', array('form' => $form->createView()));
    }
}
