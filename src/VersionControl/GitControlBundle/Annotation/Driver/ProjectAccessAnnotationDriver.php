<?php
namespace VersionControl\GitControlBundle\Annotation\Driver;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use VersionControl\GitControlBundle\Controller\Base\BaseProjectController;

class ProjectAccessAnnotationDriver{
    
    /**
     * Annotation Reader
     * @var Doctrine\Common\Annotations\Reader 
     */
    private $reader;
    
    /**
     * 
     * @param Doctrine\Common\Annotations\Reader $reader
     */
    public function __construct($reader)
    {
        $this->reader = $reader;
    }
    
    /**
     * This event will fire during any controller call
     * 
     * @param FilterControllerEvent $event
     * @return type
     * @throws AccessDeniedHttpException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        if (!is_array($controller = $event->getController())) { //return if no controller
            return;
        }
        
        $object = new \ReflectionObject($controller[0]);// get controller
        $method = $object->getMethod($controller[1]);// get method

        $configurations = $this->reader->getMethodAnnotations($method);
        
        foreach ($configurations as $configuration) { //Start of annotations reading
            
            if(isset($configuration->grantType) && $controller[0] instanceof BaseProjectController){//Found our annotation
                $controller[0]->setProjectGrantType($configuration->grantType);
                $request = $controller[0]->get('request_stack')->getCurrentRequest();
                $id = $request->get('id', false);
                if($id !== false){
                    $controller[0]->initAction($id,$configuration->grantType);
                }
             }
         }
    }
}