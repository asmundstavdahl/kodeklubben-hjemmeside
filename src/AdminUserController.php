<?php













/**
 * Class AdminUserController.
 *
 * @Route("/kontrollpanel")
 */
class AdminUserController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/brukere", name="cp_users")
     * @Method("GET")
     */
    public function showAction(Request $request)
    {
        $club = self::get('club_manager')->getCurrentClub();
        $searchQuery = $request->query->get('search', '');

        $query = self::getDoctrine()->getRepository('AppBundle:User')->findFilteredByClubQuery($club, $searchQuery);

        $paginator = self::get('knp_paginator');
        $users = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            20/*limit per page*/
        );

        return self::render('user/show_users.html.twig', array(
            'users' => $users,
            'searchQuery' => $searchQuery,
        ));
    }

    /**
     * @param User $user
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/brukere/{id}", name="cp_user")
     * @Method("GET")
     */
    public function showSpecificAction(User $user)
    {
        self::get('club_manager')->denyIfNotCurrentClub($user);

        return self::render('user/user_specific.html.twig', array(
            'user' => $user,
        ));
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @Route("/bruker/ny", name="cp_user_create")
     * @Method({"GET", "POST"})
     */
    public function createUserAction(Request $request)
    {
        $userRegistration = self::get('user.registration');
        $user = $userRegistration->newUser();

        $userRegistration->setRandomEncodedPassword($user);

        $form = self::createForm(AdminUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $role = $form['role']->getData();
            if (self::get('user.roles')->isValidRole($role)) {
                $user->setRoles([$role]);
                $userRegistration->persistUserAndSendNewUserCode($user);
            }

            return self::redirectToRoute('cp_users');
        }

        return self::render('user/create_user.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @Route("/bruker/type",
     *     options = { "expose" = true },
     *     name="cp_change_user_type"
     * )
     * @Method({"POST"})
     */
    public function changeUserTypeAction(Request $request)
    {
        $userId = $request->request->get('userId');
        $role = $request->request->get('role');

        $userRole = 'ROLE_'.strtoupper($role);

        $user = self::getDoctrine()->getRepository('AppBundle:User')->find($userId);

        if (!self::get('user.roles')->isValidRole($userRole) ||
            $user === self::getUser()
        ) {
            throw new BadRequestHttpException();
        }

        self::get('club_manager')->denyIfNotCurrentClub($user);

        $currentUserRole = $user->getRoles()[0];
        //Check if trying to change to current role
        if ($userRole === $currentUserRole) {
            return new JsonResponse(array('status' => 'success'));
        }

        $manager = self::getDoctrine()->getManager();

        switch ($currentUserRole) {
            case 'ROLE_PARENT':
                //Remove participation from all courses this and future semesters
                self::removeCurrentParticipants($user);
                //Remove all children
                self::removeChildren($user);
                break;
            case 'ROLE_PARTICIPANT':
                //Remove participation from all courses this and future semesters
                self::removeCurrentParticipants($user);
                break;

            case 'ROLE_ADMIN':
            case 'ROLE_TUTOR':
                //If user is changed to Participant or Parent
                if ($userRole === 'ROLE_PARTICIPANT' || $userRole === 'ROLE_PARENT') {
                    self::removeCurrentTutors($user);
                }
        }
        //Update user role
        $user->removeRoles();
        $user->addRole($userRole);
        $manager->persist($user);

        $manager->flush();

        return new JsonResponse(array('status' => 'success'));
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @Route("/bruker/slett",
     *     options = { "expose" = true },
     *     name="cp_user_delete"
     * )
     * @Method({"POST"})
     */
    public function deleteAction(Request $request)
    {
        $userId = $request->request->get('userId');

        if ($userId === null) {
            throw new BadRequestHttpException();
        }

        self::get('logger')->info("Deleting user with id $userId");

        $user = self::getDoctrine()->getRepository('AppBundle:User')->find($userId);

        if ($user === null) {
            throw self::createNotFoundException();
        }

        self::get('club_manager')->denyIfNotCurrentClub($user);

        if ($user === self::getUser()) {
            throw new AccessDeniedException('It\'s illegal to kill yourself');
        }

        $em = self::getDoctrine()->getManager();
        $em->remove($user);
        $em->flush();

        self::get('logger')->info("User with name {$user} deleted");

        return new JsonResponse(array('status' => 'success'));
    }

    private function removeCurrentParticipants($user)
    {
        //Remove participation from all courses this and future semesters
        $participants = self::getDoctrine()->getRepository('AppBundle:Participant')->findByUserThisAndLaterSemesters($user);
        $manager = self::getDoctrine()->getManager();
        foreach ($participants as $participant) {
            $manager->remove($participant);
        }
        $manager->flush();
    }

    private function removeCurrentTutors($user)
    {
        //Remove tutor from all courses this and future semesters
        $manager = self::getDoctrine()->getManager();
        $tutors = self::getDoctrine()->getRepository('AppBundle:Tutor')->findByUserThisAndLaterSemesters($user);
        foreach ($tutors as $tutor) {
            $manager->remove($tutor);
        }
        $manager->flush();
    }

    private function removeChildren($user)
    {
        $manager = self::getDoctrine()->getManager();
        $children = self::getDoctrine()->getRepository('AppBundle:Child')->findBy(array('parent' => $user));
        foreach ($children as $child) {
            $manager->remove($child);
        }
        $manager->flush();
    }
}
