<?php











class ChildController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @Route("/barn/ny", name="child_create")
     * @Method({"GET", "POST"})
     */
    public function createChildAction(Request $request)
    {
        $child = new Child();
        $form = self::createForm(ChildType::class, $child);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $child->setParent(self::getUser());
            $manager = self::getDoctrine()->getManager();
            $manager->persist($child);
            $manager->flush();

            return self::redirectToRoute('sign_up');
        }

        return self::render('course/sign_up/create_child.html.twig', array('form' => $form->createView()));
    }

    /**
     * @param User    $user
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @Route("/kontrollpanel/barn/ny/{user}", name="cp_child_create")
     * @Method({"GET", "POST"})
     */
    public function adminCreateChildAction(User $user, Request $request)
    {
        self::get('club_manager')->denyIfNotCurrentClub($user);

        $child = new Child();
        $form = self::createForm(ChildType::class, $child);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $child->setParent($user);
            $manager = self::getDoctrine()->getManager();
            $manager->persist($child);
            $manager->flush();

            return self::redirectToRoute('cp_sign_up', array('id' => $user->getId()));
        }

        return self::render('course/control_panel/sign_up/create_child.html.twig', array(
            'form' => $form->createView(),
            'user' => $user,
        ));
    }

    /**
     * @param Child   $child
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/barn/slett/{id}",
     *     requirements={"id" = "\d+"},
     *     name="child_delete"
     * )
     * @Method("POST")
     */
    public function deleteChildAction(Child $child, Request $request)
    {
        self::get('club_manager')->denyIfNotCurrentClub($child);

        $isAdmin = self::get('security.authorization_checker')->isGranted('ROLE_ADMIN');
        //A parent can only delete their own children
        if ($child->getParent()->getId() == self::getUser()->getId() || $isAdmin) {
            $childParticipants = self::getDoctrine()->getRepository('AppBundle:Participant')->findBy(array('child' => $child));
            $manager = self::getDoctrine()->getManager();
            $manager->remove($child);
            //Remove all child participation
            foreach ($childParticipants as $participant) {
                $manager->remove($participant);
            }
            $manager->flush();
        }

        return self::redirect($request->headers->get('referer'));
    }
}
