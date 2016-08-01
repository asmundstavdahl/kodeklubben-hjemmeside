<?php

namespace CourseBundle\Controller;

use CourseBundle\Form\CourseTypeType;
use CourseBundle\Entity\CourseType;
use ImageBundle\Entity\Image;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class AdminCourseTypeController extends Controller
{
    public function showAction()
    {
        $courses = $this->getDoctrine()->getRepository('CourseBundle:CourseType')->findAll();
        return $this->render('@Course/control_panel/show_course_type.html.twig', array(
            'courses' => $courses,
        ));
    }

    public function editCourseTypeAction(Request $request, CourseType $courseType = null)
    {
        // Check if this is a create or edit
        $isCreate = is_null($courseType);
        if ($isCreate) {
            $club = $this->get('app.club_finder')->getCurrentClub();

            // Initialize a new CourseType with a new image
            $image = new Image();
            $image->setClub($club);

            $courseType = new CourseType();
            $courseType->setClub($club);
            $courseType->setImage($image);
        }

        $form = $this->createForm(new CourseTypeType($isCreate), $courseType);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Upload image
            $image = $courseType->getImage();
            if(!is_null($image->getFile())){
                $image->setName($courseType->getName());
                $this->get('app.image_uploader')->uploadImage($image);
            }

            // Save CourseType
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($courseType);
            $manager->flush();
            return $this->redirectToRoute('cp_course_type');
        }
        return $this->render('@Course/control_panel/show_edit_course_type.html.twig', array(
            'courseType' => $courseType,
            'form' => $form->createView()
        ));
    }

    public function deleteCourseTypeAction(CourseType $courseType)
    {
        // Soft delte CourseType
        $courseType->delete();
        $manager = $this->getDoctrine()->getManager();
        $manager->persist($courseType);
        $manager->flush();
        return $this->redirectToRoute('cp_course_type');
    }

}