<?php











class CourseQueueController extends Controller
{
    /**
     * @param Course $course
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/kontrollpanel/kurs/venteliste/{id}", name="cp_course_queue")
     * @Method("GET")
     */
    public function showAction(Course $course)
    {
        $queueEntities = self::getDoctrine()->getRepository('AppBundle:CourseQueueEntity')->findByCourse($course);

        return self::render('course/course_queue/show_course_queue.html.twig', array(
            'course' => $course,
            'queueEntities' => $queueEntities,
        ));
    }

    /**
     * @param Request $request
     * @param Course  $course
     *
     * @Route("/pamelding/venteliste/{id}", name="course_enqueue")
     * @Method("POST")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function enqueueAction(Request $request, Course $course)
    {
        $queueEntity = new CourseQueueEntity();

        if (self::isGranted('ROLE_PARENT')) {
            $childId = $request->get('child');

            if (null === $childId || null === $child = self::getDoctrine()->getRepository('AppBundle:Child')->find($childId)) {
                throw new BadRequestHttpException();
            }
            $alreadyInQueue = null !== self::getDoctrine()->getRepository('AppBundle:CourseQueueEntity')->findByCourseAndChild($course, $child);
            $alreadyInCourse = null !== self::getDoctrine()->getRepository('AppBundle:Participant')->findByCourseAndChild($course, $child);

            $queueEntity->setChild($child);
        } else {
            $alreadyInQueue = null !== self::getDoctrine()->getRepository('AppBundle:CourseQueueEntity')->findOneByUserAndCourse(self::getUser(), $course);
            $alreadyInCourse = null !== self::getDoctrine()->getRepository('AppBundle:Participant')->findOneByUserAndCourse(self::getUser(), $course);
        }

        $queueEntity->setUser(self::getUser());

        $queueEntity->setCourse($course);

        $name = self::isGranted('ROLE_PARENT') ? $queueEntity->__toString() : 'Du';

        $validator = self::get('validator');
        $errors = $validator->validate($queueEntity);

        if ($alreadyInCourse) {
            self::addFlash('warning', "{$name} er allerede påmeldt {$course}: {$course->getDescription()}.");
        } elseif ($alreadyInQueue) {
            self::addFlash('warning', "{$name} er allerede på ventelisten til {$course}: {$course->getDescription()}.");
        } elseif (count($errors) > 0) {
            self::addFlash('danger', 'Det har oppstått en feil. Ingen handling utført.');
        } else {
            $em = self::getDoctrine()->getManager();
            $em->persist($queueEntity);
            $em->flush();

            self::addFlash('success', "{$name} har blitt lagt til i ventelisten til {$course}: {$course->getDescription()}.");
        }

        return self::redirectToRoute('sign_up');
    }

    /**
     * @param Request           $request
     * @param CourseQueueEntity $queueEntity
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("pamelding/venteliste/meldav/{id}", name="queue_entity_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, CourseQueueEntity $queueEntity)
    {
        if ($queueEntity->getUser() !== self::getUser()) {
            throw new BadRequestHttpException();
        }

        $child = $queueEntity->getChild();

        $em = self::getDoctrine()->getManager();
        $em->remove($queueEntity);
        $em->flush();

        if ($child !== null) {
            self::addFlash('success', "{$child} ble meldt av ventelisten til {$queueEntity->getCourse()}");
        } else {
            self::addFlash('success', "Du ble meldt av ventelisten til {$queueEntity->getCourse()}");
        }

        return self::redirect($request->headers->get('referer'));
    }
}
