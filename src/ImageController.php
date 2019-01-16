<?php










class ImageController extends Controller
{
    /**
     * @param Request $request
     * @param $name
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @Route("kontrollpanel/bilde/last_opp/{name}", name="image_upload")
     * @Method({"GET", "POST"})
     */
    public function uploadImageAction(Request $request, $name)
    {
        $club = self::get('club_manager')->getCurrentClub();
        $image = self::getDoctrine()->getRepository('AppBundle:Image')->findByClubAndName($club, $name);

        if ($image === null) {
            $image = new Image();
            $image->setClub($club);
            $image->setName($name);
        }

        $form = self::createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            self::get('app.image_uploader')->uploadImage($image);

            return self::redirectToRoute('home');
        }

        return self::render('image/upload_image.html.twig', array(
            'form' => $form->createView(),
            'image' => $image,
        ));
    }
}
