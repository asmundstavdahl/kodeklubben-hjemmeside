<?php












class UserController extends Controller
{
    private $roleTranslate = array(
        'ROLE_PARTICIPANT' => 'Deltaker',
        'ROLE_PARENT' => 'Foresatt',
        'ROLE_TUTOR' => 'Veileder',
        'ROLE_ADMIN' => 'Admin',
    );
    public function showRegistrationOptionsAction()
    {
        return self::render('user/registration_options.html.twig');
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/registrer/deltaker", name="participant_registration")
     * @Method({"GET", "POST"})
     */
    public function registerParticipantAction(Request $request)
    {
        return self::registerUser('ROLE_PARTICIPANT', $request);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/registrer/foresatt", name="parent_registration")
     * @Method({"GET", "POST"})
     */
    public function registerParentAction(Request $request)
    {
        return self::registerUser('ROLE_PARENT', $request);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/registrer/veileder", name="tutor_registration")
     * @Method({"GET", "POST"})
     */
    public function registerTutorAction(Request $request)
    {
        return self::registerUser('ROLE_TUTOR', $request);
    }

    /**
     * @param string  $role
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function registerUser($role, Request $request)
    {
        $user = self::get('user.registration')->newUser();
        $user->setRoles(array($role));
        $form = self::createForm(UserType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            self::handleRegistrationForm($user);

            return self::redirectToRoute('security_login_form', array('last_username' => $user->getUsername()));
        }

        return self::render(
            'user/registration.html.twig',
            array('form' => $form->createView(), 'role' => self::roleTranslate[$role])
        );
    }

    /**
     * @param User $user
     *
     * Encrypts password and persists user to database
     */
    private function handleRegistrationForm(User $user)
    {
        $password = self::get('security.password_encoder')
            ->encodePassword($user, $user->getPassword());
        $user->setPassword($password);

        $em = self::getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @Route("/registrer/{code}", name="registration_new_user_code")
     * @Method({"GET", "POST"})
     */
    public function registerWithNewUserCodeAction(Request $request)
    {
        $userRegistration = self::get('user.registration');
        $code = $request->get('code');
        $hashedCode = $userRegistration->hashNewUserCode($code);
        $user = self::getDoctrine()->getRepository('AppBundle:User')->findOneBy(array('newUserCode' => $hashedCode));
        if (!$user) {
            return self::render('base/error.html.twig', array(
            'error' => 'Ugyldig kode eller brukeren er allerede opprettet',
            ));
        } else {
            // Force user to create new password
            $user->setPassword(null);
        }
        self::get('club_manager')->denyIfNotCurrentClub($user);

        $form = self::createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setNewUserCode(null);
            $user->setPassword($userRegistration->encodePassword($user, $form['password']->getData()));
            $userRegistration->persistUser($user);

            return self::redirectToRoute('security_login_form');
        }

        return self::render('user/registration.html.twig', array(
            'form' => $form->createView(),
            'role' => self::roleTranslate[$user->getRoles()[0]],
        ));
    }

    /**
     * @param Request $request
     * @Route("/bruker/endre", name="user_update")
     * @Method({"GET", "POST"})
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editUserAction(Request $request)
    {
        $user = self::getUser();

        $form = self::createForm(UserInfoType::class, $user);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = self::getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            self::addFlash('success', 'Kontoinnstillingene ble lagret.');

            return self::redirectToRoute('user_update');
        }

        return self::render('user/update_user_info.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @param Request $request
     * @Route("/bruker/passord", name="user_update_password")
     * @Method({"GET", "POST"})
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editPasswordAction(Request $request)
    {
        $user = self::getUser();

        $form = self::createForm(NewPasswordType::class);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $hashedPassword = self::get('security.password_encoder')->encodePassword($user, $form->get('password')->getData());
            $user->setPassword($hashedPassword);
            $em = self::getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            self::addFlash('success', 'Passordet ble endret.');

            return self::redirectToRoute('user_update_password');
        }

        return self::render('user/update_password.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
