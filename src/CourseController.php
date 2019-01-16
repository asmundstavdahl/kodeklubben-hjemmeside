<?php









class CourseController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/kurs", name="courses")
     * @Method("GET")
     */
    public function showAction()
    {
        $club = self::get('club_manager')->getCurrentClub();
        $courses = self::getDoctrine()->getRepository('AppBundle:CourseType')->findAllByClub($club);

        return self::render('course/show.html.twig', array(
            'courses' => $courses, ));
    }

    /**
     * @param Course $course
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/kurs/{id}",
     *     options={"expose"=true},
     *     requirements={"id"="\d+"},
     *     name="course_info"
     * )
     * @Method("GET")
     */
    public function showCourseInfoAction(Course $course)
    {
        self::get('course.manager')->throw404ifCourseOrCourseTypeIsDeleted($course);

        self::get('club_manager')->denyIfNotCurrentClub($course);

        return self::render('course/course_info.html.twig', array('course' => $course));
    }

    /**
     * @param $week
     *
     * @return JsonResponse
     *
     * @Route("/api/kurs/uke/{week}",
     *     name="api_get_course_classes_by_week",
     *     requirements={"id" = "\d+"}
     * )
     * @Method("GET")
     */
    public function getCourseClassesAction($week)
    {
        $currentSemester = self::getDoctrine()->getRepository('AppBundle:Semester')->findCurrentSemester();
        $club = self::get('club_manager')->getCurrentClub();
        $courseClasses = self::getDoctrine()->getRepository('AppBundle:CourseClass')->findByWeek($week, $currentSemester, $club);

        return new JsonResponse($courseClasses);
    }
}
