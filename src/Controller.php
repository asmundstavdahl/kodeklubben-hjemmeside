<?php

class Controller
{
    use \Rapd\Controller\Prototype;

    public function render(string $templateName, array $data = [])
    {
    	return View::render($templateName, $data);
    }
}
