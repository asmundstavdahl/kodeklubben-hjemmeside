<?php










/**
 * Class MessageController.
 *
 * @Route("/kontrollpanel")
 */
class MessageController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @Route("/melding", name="cp_message")
     * @Method({"GET", "POST"})
     */
    public function showAction(Request $request)
    {
        $club = self::get('club_manager')->getCurrentClub();
        $messages = self::getDoctrine()->getRepository('AppBundle:Message')->findLatestMessages($club);

        $message = new Message();
        $message->setClub($club);

        $form = self::createForm(MessageType::class, $message);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager = self::getDoctrine()->getManager();
            $manager->persist($message);
            $manager->flush();

            return self::redirectToRoute('cp_message');
        }

        return self::render('control_panel/show_message.html.twig', array(
            'messages' => $messages,
            'form' => $form->createView(),
        ));
    }

    /**
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/melding/slett/{id}",
     *     name="cp_delete_message",
     *     requirements={"id" = "\d+"}
     * )
     *
     * @Method({"POST"})
     */
    public function deleteMessageAction($id)
    {
        $manager = self::getDoctrine()->getManager();
        $message = $manager->getRepository('AppBundle:Message')->find($id);
        self::get('club_manager')->denyIfNotCurrentClub($message);

        if (!is_null($message)) {
            $manager->remove($message);
            $manager->flush();
        }

        return self::redirectToRoute('cp_message');
    }
}
