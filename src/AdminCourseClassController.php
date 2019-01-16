<?php











/**
 * Class AdminCourseClassController.
 *
 * @Route("/kontrollpanel")
 */
class AdminCourseClassController extends Controller
{
    /**
     * @param Request $request
     * @param Course  $course
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @Route("/kurs/timeplan/{id}",
     *     requirements={"id" = "\d+"},
     *     name="cp_course_time_table"
     * )
     * @Method({"GET", "POST"})
     */
    public function editTimeTableAction(Request $request, Course $course)
    {
        self::get('course.manager')->throw404ifCourseOrCourseTypeIsDeleted($course);

        self::get('club_manager')->denyIfNotCurrentClub($course);

        $courseClass = new CourseClass();
        $latestCourseClass = self::getDoctrine()->getRepository('AppBundle:CourseClass')->findOneBy(array('course' => $course), array('time' => 'DESC'), 1);

        //Set default time to one week after last class
        if (!is_null($latestCourseClass)) {
            $courseClass->setTime(clone $latestCourseClass->getTime());
            $courseClass->getTime()->modify('+7day');
            $courseClass->setPlace($latestCourseClass->getPlace());
        } else {
            $courseClass->setTime(new \DateTime());
        }

        $form = self::createForm(CourseClassType::class, $courseClass);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $courseClass->setCourse($course);
            $manager = self::getDoctrine()->getManager();
            $manager->persist($courseClass);
            $manager->flush();

            return self::redirectToRoute('cp_course_time_table', array('id' => $course->getId()));
        }

        return self::render('course/control_panel/show_time_table.html.twig', array(
            'course' => $course,
            'form' => $form->createView(),
        ));
    }

    /**
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/kursdag/slett/{id}",
     *     requirements={"id" = "\d+"},
     *     name="cp_delete_course_class"
     * )
     * @Method("POST")
     */
    public function deleteCourseClassAction($id)
    {
        $manager = self::getDoctrine()->getManager();
        $courseClass = $manager->getRepository('AppBundle:CourseClass')->find($id);
        self::get('club_manager')->denyIfNotCurrentClub($courseClass->getCourse());

        if (!is_null($courseClass)) {
            $manager->remove($courseClass);
            $manager->flush();
        }

        return self::redirectToRoute('cp_course_time_table', array(
            'id' => $courseClass->getCourse()->getId(),
        ));
    }
}
