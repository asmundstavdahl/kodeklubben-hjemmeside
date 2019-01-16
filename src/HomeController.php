<?php







class HomeController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/", name="home")
     * @Method("GET")
     */
    public function showAction()
    {
        return self::render('home/show.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route(
     *     "/",
     *     name="home_w_domain",
     *     host="{subdomain}.{domain}",
     *     defaults={"subdomain"="", "domain"="%base_host%"},
     *     requirements={"subdomain"="\w+", "domain"="%base_host%"}
     *     )
     * @Method("GET")
     */
    public function showWithDomainAction()
    {
        return self::showAction();
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showMessagesAction()
    {
        $club = self::get('club_manager')->getCurrentClub();
        $messages = self::getDoctrine()->getRepository('AppBundle:Message')->findLatestMessages($club);

        return self::render('home/messages.html.twig', array('messages' => $messages));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showCourseTypesAction()
    {
        $currentSemester = self::getDoctrine()->getRepository('AppBundle:Semester')->findCurrentSemester();
        $club = self::get('club_manager')->getCurrentClub();
        $courseTypes = self::getDoctrine()->getRepository('AppBundle:CourseType')->findNotHiddenBySemester($currentSemester, $club);

        return self::render('home/course.html.twig', array('courseTypes' => $courseTypes));
    }

    /**
     * @param null $week
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showTimeTableAction($week = null)
    {
        if (is_null($week)) {
            $week = (new \DateTime())->format('W');
        }
        $currentSemester = self::getDoctrine()->getRepository('AppBundle:Semester')->findCurrentSemester();
        $club = self::get('club_manager')->getCurrentClub();
        $courseClasses = self::getDoctrine()->getRepository('AppBundle:CourseClass')->findByWeek($week, $currentSemester, $club);
        $allCourseClasses = self::getDoctrine()->getRepository('AppBundle:CourseClass')->findBySemester($currentSemester);

        $firstClass = reset($allCourseClasses);
        $lastClass = end($allCourseClasses);
        $coursesHasStarted = $firstClass !== false && $firstClass->getTime() < new \DateTime();
        $coursesHasEnded = $lastClass !== false && $lastClass->getTime() < new \DateTime();

        return self::render('home/time_table.html.twig', array(
            'courseClasses' => $courseClasses,
            'week' => $week,
            'currentSemester' => $currentSemester,
            'firstClass' => $firstClass,
            'lastClass' => $lastClass,
            'coursesHasStarted' => $coursesHasStarted,
            'coursesHasEnded' => $coursesHasEnded
        ));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showSponsorsAction()
    {
        $club = self::get('club_manager')->getCurrentClub();
        $clubSponsors = self::getDoctrine()->getRepository('AppBundle:Sponsor')->findAllByClub($club);

        return self::render('home/sponsors.html.twig', array(
            'sponsors' => $clubSponsors,
        ));
    }
}
