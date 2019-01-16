<?php










/**
 * Class AdminCourseController.
 *
 * @Route("/kontrollpanel")
 */
class AdminCourseController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/kurs", name="cp_course")
     * @Method("GET")
     */
    public function showAction(Request $request)
    {
        $semesterId = $request->query->get('semester');
        $semesterRepo = self::getDoctrine()->getRepository('AppBundle:Semester');
        if (!is_null($semesterId)) {
            $semester = $semesterRepo->find($semesterId);
        } else {
            $semester = $semesterRepo->findCurrentSemester();
        }
        $semesters = $semesterRepo->findAll();
        $club = self::get('club_manager')->getCurrentClub();
        $courses = self::getDoctrine()->getRepository('AppBundle:Course')->findBySemester($semester, $club);

        return self::render('course/control_panel/show.html.twig', array(
            'courses' => $courses,
            'semester' => $semester,
            'semesters' => $semesters,
        ));
    }

    /**
     * @param Request     $request
     * @param Course|null $course
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @Route("/kurs/ny", name="cp_create_course")
     * @Route("/kurs/{id}", name="cp_edit_course", requirements={"id" = "\d+"})
     * @Method({"GET", "POST"})
     */
    public function editCourseAction(Request $request, Course $course = null)
    {
        self::get('course.manager')->throw404ifCourseOrCourseTypeIsDeleted($course);

        $isCreateAction = is_null($course);
        if ($isCreateAction) {
            $course = new Course();
        } else {
            self::get('club_manager')->denyIfNotCurrentClub($course);
        }
        $club = self::get('club_manager')->getCurrentClub();
        $form = self::createForm(CourseFormType::class, $course, array(
            'showAllSemesters' => !$isCreateAction,
            'club' => $club,
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager = self::getDoctrine()->getManager();
            $manager->persist($course);
            $manager->flush();

            self::get('course.queue_manager')->promoteParticipantsFromQueueToCourse($course);

            return self::redirectToRoute('cp_course');
        }

        return self::render('course/control_panel/show_create_course.html.twig', array(
            'course' => $course,
            'form' => $form->createView(),
        ));
    }

    /**
     * @param Course $course
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/kurs/slett/{id}",
     *     requirements={"id" = "\d+"},
     *     name="cp_delete_course"
     * )
     * @Method("POST")
     */
    public function deleteCourseAction(Course $course)
    {
        self::get('course.manager')->throw404ifCourseOrCourseTypeIsDeleted($course);

        self::get('club_manager')->denyIfNotCurrentClub($course);

        $course->delete();
        $manager = self::getDoctrine()->getManager();
        $manager->persist($course);
        $manager->flush();

        return self::redirectToRoute('cp_course');
    }

    /**
     * @param Course $course
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/kurs/deltakere/{id}",
     *     requirements={"id" = "\d+"},
     *     name="cp_course_participants"
     * )
     * @Method("GET")
     */
    public function showParticipantsAction(Course $course)
    {
        self::get('course.manager')->throw404ifCourseOrCourseTypeIsDeleted($course);

        self::get('club_manager')->denyIfNotCurrentClub($course);

        return self::render('course/control_panel/show_course_participants.html.twig', array('course' => $course));
    }

    /**
     * @param Course $course
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/kurs/veiledere/{id}",
     *     requirements={"id" = "\d+"},
     *     name="cp_course_tutors"
     * )
     * @Method("GET")
     */
    public function showTutorsAction(Course $course)
    {
        self::get('course.manager')->throw404ifCourseOrCourseTypeIsDeleted($course);

        self::get('club_manager')->denyIfNotCurrentClub($course);

        return self::render('course/control_panel/show_course_tutors.html.twig', array('course' => $course));
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/veiledere", name="cp_tutors")
     * @Method("GET")
     */
    public function showAllTutorsAction(Request $request)
    {
        return self::renderCourseUsers($request, 'course/control_panel/show_tutors.html.twig');
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/deltakere", name="cp_participants")
     * @Method("GET")
     */
    public function showAllParticipantsAction(Request $request)
    {
        return self::renderCourseUsers($request, 'course/control_panel/show_participants.html.twig');
    }

    private function renderCourseUsers(Request $request, $template)
    {
        $semesterId = $request->query->get('semester');
        $semesterRepo = self::getDoctrine()->getRepository('AppBundle:Semester');
        $semester = is_null($semesterId) ? $semesterRepo->findCurrentSemester() : $semesterRepo->find($semesterId);
        $club = self::get('club_manager')->getCurrentClub();
        $courses = self::getDoctrine()->getRepository('AppBundle:Course')->findBySemester($semester, $club);
        $semesters = $semesterRepo->findAll();

        return self::render($template, array(
            'courses' => $courses,
            'semester' => $semester,
            'semesters' => $semesters,
        ));
    }
}
