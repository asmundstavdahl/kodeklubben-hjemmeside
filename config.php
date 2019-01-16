<?php


use \Rapd\Database;

$dbFile = __DIR__."/app.sqlite3";
Database::$pdo = new PDO("sqlite:{$dbFile}");
Database::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


class Environment extends \Rapd\Environment {};

# Set some environment variables used by the header template
# For JS, CSS, images etc.: (ASSET_BASE)/css/app.css
Environment::set("ASSET_BASE", "");
# Page meta for html/head
Environment::set("TITLE", "Kodeklubben Trondheim");
Environment::set("AUTHOR", "Åsmund Stavdahl");
Environment::set("DESCRIPTION", "Default description of the rapd/skeleton");


class Router extends \Rapd\Router {};
class Route extends \Rapd\Router\Route
{
    function __toString()
    {
        return "<".get_called_class()."#{$this->name}>";
    }
};

Router::setBasePath(Environment::get("ASSET_BASE"));


class View extends \Rapd\View {};

$loader = new Twig_Loader_Filesystem(__DIR__."/templates");
$twig = new Twig_Environment($loader, [
    "cache" => __DIR__."/var/cache",
    "debug" => true,
]);
#$twig->addExtension(new \AppBundle\Twig\CodeClubExtension());
#$twig->addExtension(new \AppBundle\Twig\ImageExtension());
#$twig->addExtension(new \AppBundle\Twig\SemesterExtension());
#$twig->addExtension(new \AppBundle\Twig\SignupExtension());
#$twig->addExtension(new \AppBundle\Twig\StaticContentExtension());
#$twig->addExtension(new \AppBundle\Twig\UserExtension());
$twig->addExtension(new \AppBundle\Twig\MockExtension());

# Configure a function to be used by View::render()
View::setRenderer(function(string $template, array $data = []) use ($twig) {
    $data = array_merge(Environment::getAll(), $data);
    $output = $twig->render($template, $data);
    return $output;
});

function setTitle($newTitle)
{
    Environment::set("TITLE", "{$newTitle} - Kodeklubben Trondheim");
}

View::render("home/show.html.twig");
