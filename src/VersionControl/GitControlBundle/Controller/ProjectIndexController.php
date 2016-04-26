<?php

namespace VersionControl\GitControlBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;


use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use VersionControl\GitControlBundle\Controller\Base\BaseProjectController;
use VersionControl\GitControlBundle\Annotation\ProjectAccess;

/**
 * Project controller.
 *
 * @Route("/project/{id}")
 * 
 */
class ProjectIndexController extends BaseProjectController
{

    /**
     * Lists all Project entities.
     *
     * @Route("/", name="project")
     * @Method("GET")
     * @Template()
     * @ProjectAccess(grantType="VIEW")
     */
    public function indexAction(Request $request)
    {
        //Get latest from updates from remot branches
        $response = $this->gitCommands->command('branch')->fetchAll();
        
        $pushPullCommitCount = $this->gitCommands->command('sync')->commitCountWithRemote($this->branchName);
        
        $statusCount = $this->gitCommands->command('commit')->countStatus();

        //$this->get('session')->getFlashBag()->add('notice', $response);
        
        return array_merge($this->viewVariables, array(
            'pushPullCommitCount' => $pushPullCommitCount,
            'statusCount' => $statusCount,
            ));
    }
    
}