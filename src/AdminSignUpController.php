<?php















/**
 * Class AdminSignUpController.
 *
 * @Route("/kontrollpanel")
 */
class AdminSignUpController extends Controller
{
    /**
     * @param User $user
     *
     * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\Security\Core\Exception\AccessDeniedException
     *
     * @Route("/pamelding/{id}",
     *     requirements={"id" = "\d+"},
     *     name="cp_sign_up"
     * )
     * @Method("GET")
     */
    public function showAction(User $user)
    {
        self::get('club_manager')->denyIfNotCurrentClub($user);
        $currentSemester = self::getDoctrine()->getRepository('AppBundle:Semester')->findCurrentSemester();
        $club = self::get('club_manager')->getCurrentClub();
        $allCourseTypes = self::getDoctrine()->getRepository('AppBundle:CourseType')->findAllByClub($club);
        $courseTypes = self::filterActiveCourses($allCourseTypes);
        $parameters = array(
            'currentSemester' => $currentSemester,
            'courseTypes' => $courseTypes,
            'user' => $user,
        );
        if (in_array('ROLE_PARENT', $user->getRoles())) {
            $participants = self::getDoctrine()->getRepository('AppBundle:Participant')->findBy(array('user' => $user));
            $children = self::getDoctrine()->getRepository('AppBundle:Child')->findBy(array('parent' => $user));

            return self::render('course/control_panel/sign_up/parent.html.twig', array_merge($parameters, array(
                'participants' => $participants,
                'children' => $children,
            )));
        } elseif (in_array('ROLE_PARTICIPANT', $user->getRoles())) {
            $participants = self::getDoctrine()->getRepository('AppBundle:Participant')->findBy(array('user' => $user));

            return self::render('course/control_panel/sign_up/participant.html.twig', array_merge($parameters, array(
                'participants' => $participants,
            )));
        } elseif (in_array('ROLE_TUTOR', $user->getRoles())) {
            $tutors = self::getDoctrine()->getRepository('AppBundle:Tutor')->findBy(array('user' => $user));

            return self::render('course/control_panel/sign_up/tutor.html.twig', array_merge($parameters, array(
                'tutors' => $tutors,
            )));
        } else {
            return self::redirectToRoute('cp_users');
        }
    }

    /**
     * @param Course  $course
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/pamelding/barn/{id}",
     *     requirements={"id" = "\d+"},
     *     name="cp_sign_up_course_child"
     * )
     * @Method("POST")
     */
    public function signUpChildAction(Course $course, Request $request)
    {
        self::get('club_manager')->denyIfNotCurrentClub($course);

        $childId = $request->request->get('child');
        $child = self::getDoctrine()->getRepository('AppBundle:Child')->find($childId);
        self::get('club_manager')->denyIfNotCurrentClub($child);
        if ($child === null) {
            throw new NotFoundHttpException('Child not found');
        }
        // Check if child is already signed up to the course or the course is set for another semester
        $isAlreadyParticipant = count(self::getDoctrine()->getRepository('AppBundle:Participant')->findBy(array('course' => $course, 'child' => $child))) > 0;
        $isThisSemester = $course->getSemester()->isEqualTo(self::getDoctrine()->getRepository('AppBundle:Semester')->findCurrentSemester());
        if ($isAlreadyParticipant || !$isThisSemester) {
            if ($isAlreadyParticipant) {
                self::addFlash('warning', $child.' er allerede påmeldt '.$course->getName().'. Ingen handling har blitt utført');
            } elseif (!$isThisSemester) {
                self::addFlash('danger', 'Det har skjedd en feil, vennligst prøv igjen. Kontakt oss hvis problemet vedvarer');
            }

            return self::redirect($request->headers->get('referer'));
        }
        //Check if course is full
        if (count($course->getParticipants()) >= $course->getParticipantLimit()) {
            self::addFlash('warning', $course->getName().' er fullt. '.$child.' har IKKE blitt påmeldt');

            return self::redirect($request->headers->get('referer'));
        }

        $participant = self::get('course.sign_up')->createParticipant($course, $child->getParent(), $child);
        $manager = self::getDoctrine()->getManager();
        $manager->persist($participant);
        $manager->flush();

        $flashMessage = 'Du har meldt '.$child->getFirstName().' '.$child->getLastName().' på '.$course->getName();
        self::addFlash('success', $flashMessage);

        self::get('course.queue_manager')->promoteParticipantsFromQueueToCourse($course);

        return self::redirect($request->headers->get('referer'));
    }

    /**
     * @param Course  $course
     * @param User    $user
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/pamelding/{course}/{user}",
     *     requirements={"user" = "\d+"},
     *     name="cp_sign_up_course"
     * )
     * @Method({"POST"})
     */
    public function signUpAction(Course $course, User $user, Request $request)
    {
        // Check if user is already signed up to the course or the course is set for another semester
        $isAlreadyParticipant = count(self::getDoctrine()->getRepository('AppBundle:Participant')->findBy(array('course' => $course, 'user' => $user))) > 0;
        $isAlreadyTutor = count(self::getDoctrine()->getRepository('AppBundle:Tutor')->findBy(array('course' => $course, 'user' => $user))) > 0;
        $isThisSemester = $course->getSemester()->isEqualTo(self::getDoctrine()->getRepository('AppBundle:Semester')->findCurrentSemester());
        if ($isAlreadyParticipant || $isAlreadyTutor || !$isThisSemester) {
            self::addFlash('warning', 'Du er allerede påmeldt '.$course->getName());

            return self::redirect($request->headers->get('referer'));
        }

        // Sign up as a participant if the user is logged in as a participant user
        if (in_array('ROLE_PARTICIPANT', $user->getRoles())) {
            return self::signUpParticipant($request, $course, $user);

        // Sign up as a tutor if the user is logged in as a tutor user
        } elseif (in_array('ROLE_TUTOR', $user->getRoles())) {
            return self::signUpTutor($request, $course, $user);
        } else {
            self::addFlash('danger', 'Det har skjedd en feil! Vennligst prøv igjen.');
        }

        return self::redirect($request->headers->get('referer'));
    }

    /**
     * @param Request $request
     * @param Course  $course
     * @param User    $user
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function signUpParticipant(Request $request, Course $course, User $user)
    {
        self::get('club_manager')->denyIfNotCurrentClub($course);
        self::get('club_manager')->denyIfNotCurrentClub($user);

        //Check if course is full
        if (count($course->getParticipants()) >= $course->getParticipantLimit()) {
            self::addFlash('warning', $course->getName().' er fullt. '.$user->getFullName().' har derfor IKKE blitt påmeldt.');

            return self::redirect($request->headers->get('referer'));
        }

        //Add user as participant to the course
        $participant = self::get('course.sign_up')->createParticipant($course, $user);
        $manager = self::getDoctrine()->getManager();
        $manager->persist($participant);
        $manager->flush();

        self::addFlash('success', 'Du har meldt '.$user->getFullName().' på '.$course->getName());

        self::get('course.queue_manager')->promoteParticipantsFromQueueToCourse($course);

        return self::redirect($request->headers->get('referer'));
    }

    // Ubrukt?? Se SignUpController::signUpTutorAction
    private function signUpTutor(Request $request, Course $course, User $user)
    {
        self::get('club_manager')->denyIfNotCurrentClub($course);
        self::get('club_manager')->denyIfNotCurrentClub($user);

        $isSubstitute = !is_null($request->request->get('substitute'));
        $tutor = self::get('course.sign_up')->createTutor($course, $user, $isSubstitute);
        $manager = self::getDoctrine()->getManager();
        $manager->persist($tutor);
        $manager->flush();

        $role = $isSubstitute ? 'vikar' : 'veileder';
        self::addFlash('success', 'Du har meldt '.$user->getFullName().' på '.$course->getName().' som '.$role);

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
     *     name="course_admin_withdraw_participant"
     * )
     * @Method("POST")
     */
    public function withdrawParticipantAction(Participant $participant, Request $request)
    {
        self::get('club_manager')->denyIfNotCurrentClub($participant);

        $name = $participant->getChild() === null ? $participant->getFullName() : $participant->getChild()->getFullName();

        $manager = self::getDoctrine()->getManager();
        $manager->remove($participant);
        $manager->flush();

        self::get('event_dispatcher')->dispatch(ParticipantDeletedEvent::NAME, new ParticipantDeletedEvent($participant));

        self::addFlash('success', 'Du har meldt '.$name.' av '.$participant->getCourse()->getName());

        return self::redirect($request->headers->get('referer'));
    }

    /**
     * @param Tutor   $tutor
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/pamelding/veileder/meldav/{id}",
     *     requirements={"id" = "\d+"},
     *     name="course_admin_withdraw_tutor"
     * )
     * @Method("POST")
     */
    public function withdrawTutorAction(Tutor $tutor, Request $request)
    {
        self::get('club_manager')->denyIfNotCurrentClub($tutor);

        $manager = self::getDoctrine()->getManager();
        $manager->remove($tutor);
        $manager->flush();

        self::addFlash('success', 'Du har meldt '.$tutor->getFullName().' av '.$tutor->getCourse()->getName());

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
