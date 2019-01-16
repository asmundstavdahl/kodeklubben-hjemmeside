<?php














class SignUpController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/pamelding", name="sign_up")
     * @Method("GET")
     */
    public function showAction()
    {
        if (self::get('security.authorization_checker')->isGranted('ROLE_PARENT')) {
            return self::showParentAction();
        } elseif (self::get('security.authorization_checker')->isGranted('ROLE_PARTICIPANT')) {
            return self::showParticipantAction();
        } elseif (self::get('security.authorization_checker')->isGranted('ROLE_TUTOR')) {
            return self::showTutorAction();
        } else {
            // This should never happen
            throw new AccessDeniedException();
        }
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function showParticipantAction()
    {
        $currentSemester = self::getDoctrine()->getRepository('AppBundle:Semester')->findCurrentSemester();
        $club = self::get('club_manager')->getCurrentClub();
        $allCourseTypes = self::getDoctrine()->getRepository('AppBundle:CourseType')->findAllByClub($club);
        $courseTypes = self::filterActiveCourses($allCourseTypes);
        $user = self::getUser();
        $participants = self::getDoctrine()->getRepository('AppBundle:Participant')->findByUserAndSemester($user, $currentSemester);
        $queueEntities = self::getDoctrine()->getRepository('AppBundle:CourseQueueEntity')->findByUserAndSemester($user, $currentSemester);

        return self::render('course/sign_up/participant.html.twig', array(
            'currentSemester' => $currentSemester,
            'courseTypes' => $courseTypes,
            'user' => $user,
            'participants' => $participants,
            'queueEntities' => $queueEntities,
        ));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function showParentAction()
    {
        $currentSemester = self::getDoctrine()->getRepository('AppBundle:Semester')->findCurrentSemester();
        $club = self::get('club_manager')->getCurrentClub();
        $allCourseTypes = self::getDoctrine()->getRepository('AppBundle:CourseType')->findAllByClub($club);
        $courseTypes = self::filterActiveCourses($allCourseTypes);
        $user = self::getUser();

        $participants = self::getDoctrine()->getRepository('AppBundle:Participant')->findByUserAndSemester($user, $currentSemester);
        $queueEntities = self::getDoctrine()->getRepository('AppBundle:CourseQueueEntity')->findByUserAndSemester($user, $currentSemester);
        $children = self::getDoctrine()->getRepository('AppBundle:Child')->findByParent($user);

        return self::render('course/sign_up/parent.html.twig', array(
            'currentSemester' => $currentSemester,
            'courseTypes' => $courseTypes,
            'user' => $user,
            'participants' => $participants,
            'queueEntities' => $queueEntities,
            'children' => $children,
        ));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function showTutorAction()
    {
        $currentSemester = self::getDoctrine()->getRepository('AppBundle:Semester')->findCurrentSemester();
        $club = self::get('club_manager')->getCurrentClub();
        $allCourseTypes = self::getDoctrine()->getRepository('AppBundle:CourseType')->findAllByClub($club);
        $courseTypes = self::filterActiveCourses($allCourseTypes);
        $user = self::getUser();
        $tutors = self::getDoctrine()->getRepository('AppBundle:Tutor')->findByUserAndSemester($user, $currentSemester);

        return self::render('course/sign_up/tutor.html.twig', array(
            'currentSemester' => $currentSemester,
            'courseTypes' => $courseTypes,
            'user' => $user,
            'tutors' => $tutors,
        ));
    }

    /**
     * @param Course  $course
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @internal param Child $child
     *
     * @Route("/pamelding/barn/{id}",
     *     options={"expose"=true},
     *     requirements={"id"="\d+"},
     *     name="sign_up_course_child"
     * )
     * @Method("POST")
     */
    public function signUpChildAction(Course $course, Request $request)
    {
        self::get('club_manager')->denyIfNotCurrentClub($course);
        $childId = $request->get('child');
        if ($childId === null) {
            throw new NotFoundHttpException();
        }
        $child = self::getDoctrine()->getRepository('AppBundle:Child')->find($childId);
        self::get('club_manager')->denyIfNotCurrentClub($child);
        if (!$child->getParent() === self::getUser()) {
            throw new AccessDeniedException();
        }

        // Check if child is already signed up to the course or the course is set for another semester
        $isAlreadyParticipant = self::getDoctrine()->getRepository('AppBundle:Participant')->findByCourseAndChild($course, $child) !== null;
        $isThisSemester = $course->getSemester()->isEqualTo(self::getDoctrine()->getRepository('AppBundle:Semester')->findCurrentSemester());
        if ($isAlreadyParticipant) {
            self::addFlash('warning', $child.' er allerede påmeldt '.$course->getName().'. Ingen handling har blitt utført');

            return self::redirectToRoute('sign_up');
        } elseif (!$isThisSemester) {
            self::addFlash('danger', 'Det har skjedd en feil, vennligst prøv igjen. Kontakt oss hvis problemet vedvarer');

            return self::redirectToRoute('sign_up');
        }

        //Check if course is full
        if (count($course->getParticipants()) >= $course->getParticipantLimit()) {
            self::addFlash('warning', $course->getName().' er fullt. '.$child.' har IKKE blitt påmeldt');

            return self::redirectToRoute('sign_up');
        }

        //Add child as participant to the course
        $participant = self::get('course.sign_up')->createParticipant($course, $child->getParent(), $child);
        $manager = self::getDoctrine()->getManager();
        $manager->persist($participant);
        $manager->flush();

        self::addFlash('success', 'Du har meldt '.$child->getFirstName().' '.$child->getLastName().' på '.$course->getName());

        return self::redirectToRoute('sign_up');
    }

    /**
     * @param Course  $course
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/pamelding/{id}", name="sign_up_course", requirements={"id"="\d+"})
     * @Method("POST")
     */
    public function signUpAction(Course $course, Request $request)
    {
        self::get('club_manager')->denyIfNotCurrentClub($course);

        if (self::get('security.authorization_checker')->isGranted('ROLE_PARTICIPANT')) {
            return self::signUpParticipantAction($course);
        } elseif (self::get('security.authorization_checker')->isGranted('ROLE_TUTOR')) {
            return self::signUpTutorAction($course, $request);
        } else {
            self::addFlash('danger', 'Det har skjedd en feil! Vennligst prøv igjen.');
        }

        return self::redirectToRoute('sign_up');
    }

    /**
     * @param Course $course
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function signUpParticipantAction(Course $course)
    {
        $user = self::getUser();
        $isThisSemester = $course->getSemester()->isEqualTo(self::getDoctrine()->getRepository('AppBundle:Semester')->findCurrentSemester());
        if (!self::get('security.authorization_checker')->isGranted('ROLE_PARTICIPANT') || !$isThisSemester) {
            throw new AccessDeniedException();
        }
        // Check if user is already signed up to the course or the course is set for another semester
        $isAlreadyParticipant = count(self::getDoctrine()->getRepository('AppBundle:Participant')->findBy(array('course' => $course, 'user' => $user))) > 0;
        if ($isAlreadyParticipant) {
            self::addFlash('warning', 'Du er allerede påmeldt '.$course->getName());

        //Check if course is full
        } elseif (count($course->getParticipants()) >= $course->getParticipantLimit()) {
            self::addFlash('warning', $course->getName().' er fullt. Du har derfor IKKE blitt påmeldt.');

        //Add user as participant to the course
        } else {
            $participant = self::get('course.sign_up')->createParticipant($course, $user);
            $manager = self::getDoctrine()->getManager();
            $manager->persist($participant);
            $manager->flush();

            self::addFlash('success', 'Du har meldt deg på '.$course->getName());
        }

        return self::redirectToRoute('sign_up');
    }

    /**
     * @param Course  $course
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function signUpTutorAction(Course $course, Request $request)
    {
        $user = self::getUser();
        $isThisSemester = $course->getSemester()->isEqualTo(self::getDoctrine()->getRepository('AppBundle:Semester')->findCurrentSemester());
        if (!self::get('security.authorization_checker')->isGranted('ROLE_TUTOR') || !$isThisSemester) {
            throw new AccessDeniedException();
        }
        // Check if user is already signed up to the course
        $isAlreadyTutor = self::getDoctrine()->getRepository('AppBundle:Tutor')->findByCourseAndUser($course, $user) !== null;
        if ($isAlreadyTutor) {
            self::addFlash('warning', 'Du er allerede påmeldt '.$course->getName());
        } else {
            $isSubstitute = $request->request->get('substitute') !== null;
            $tutor = self::get('course.sign_up')->createTutor($course, $user, $isSubstitute);
            $manager = self::getDoctrine()->getManager();
            $manager->persist($tutor);
            $manager->flush();

            $role = $isSubstitute ? 'vikar' : 'veileder';
            self::addFlash('success', 'Du har meldt deg på '.$course->getName().' som '.$role);

            // Send an email notification to the Club's email about the newly registered tutor
            $club = self::get('club_manager')->getCurrentClub();
            $courseName = $course->getName();
            $courseDescription = $course->getDescription();
            /*
             * @var \Swift_Mime_Message
             */
            $emailMessage = \Swift_Message::newInstance()
                ->setSubject("Ny {$role} på {$courseName} ({$courseDescription})")
                ->setFrom('ikkesvar@kodeklubben.no')
                ->setTo($club->getEmail())
                ->setBody(self::renderView('email/new_tutor_notification_email.txt.twig', [
                    'course' => $course,
                    'club' => $club,
                    'tutor' => $user,
                    'role' => $role,
                    'nTutors' => count($course->getTutors()),
                    'nSubstitutes' => count($course->getSubstitutes()),
                ]));
            $ret = self::get('mailer')->send($emailMessage);
        }

        return self::redirectToRoute('sign_up');
    }

    /**
     * @param Course  $course
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("/pamelding/veileder/meldav/{id}",
     *     requirements={"id" = "\d+"},
     *     name="withdraw_from_course_tutor"
     * )
     * @Method("POST")
     */
    public function withdrawTutorAction(Course $course, Request $request)
    {
        self::get('club_manager')->denyIfNotCurrentClub($course);

        $tutorRepo = self::getDoctrine()->getRepository('AppBundle:Tutor');
        $user = self::getUser();

        $tutor = $tutorRepo->findOneBy(array('user' => $user, 'course' => $course));

        // Check if tutor is already signed up to the course
        $isAlreadyTutor = $tutor->getCourse()->getId() === $course->getId();
        if ($isAlreadyTutor) {
            $manager = self::getDoctrine()->getManager();
            $manager->remove($tutor);
            $manager->flush();

            self::addFlash('success', 'Du har meldt deg av '.$course->getName());
        } else {
            self::addFlash('danger', 'Det skjedde en feil da vi prøvde å melde deg av '.
                $course->getName().'. vennligst prøv igjen');
        }

        return self::redirect($request->headers->get('referer'));
    }

    /**
     * @param Participant $participant
     * @param Request     $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/pamelding/deltaker/meldav/{id}",
     *     requirements={"id" = "\d+"},
     *     name="withdraw_from_course_participant"
     * )
     * @Method("POST")
     */
    public function withdrawParticipantAction(Participant $participant, Request $request)
    {
        self::get('club_manager')->denyIfNotCurrentClub($participant);

        if ($participant->getUser() === self::getUser() || self::isGranted('ROLE_ADMIN')) {
            $manager = self::getDoctrine()->getManager();
            $manager->remove($participant);
            $manager->flush();

            self::get('event_dispatcher')->dispatch(ParticipantDeletedEvent::NAME, new ParticipantDeletedEvent($participant));

            $child = $participant->getChild();

            $name = $child === null ? 'deg' : $child->__toString();

            self::addFlash('success', "Du har meldt {$name} av {$participant->getCourse()}:{$participant->getCourse()->getDescription()}");
        } else {
            self::addFlash('danger', 'Det skjedde en feil da vi prøvde å melde deg av '.
                $participant->getCourse()->getName().' vennligst prøv igjen');
        }

        return self::redirect($request->headers->get('referer'));
    }

    /**
     * @param CourseType[] $allCourseTypes
     *
     * @return array
     */
    private function filterActiveCourses($allCourseTypes)
    {
        $currentSemester = self::getDoctrine()->getRepository('AppBundle:Semester')->findCurrentSemester();
        $res = array();
        foreach ($allCourseTypes as $courseType) {
            foreach ($courseType->getCourses() as $course) {
                if ($course->getSemester()->isEqualTo($currentSemester) && !$course->isDeleted()) {
                    $courseTypeName = $courseType->getName();
                    if (!key_exists($courseTypeName, $res)) {
                        $res[$courseTypeName] = array();
                    }
                    $res[$courseTypeName][] = $course;
                }
            }
        }

        return $res;
    }
}
