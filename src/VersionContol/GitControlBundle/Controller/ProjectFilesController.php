<?php

namespace VersionContol\GitControlBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use VersionContol\GitControlBundle\Controller\Base\BaseProjectController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use VersionContol\GitControlBundle\Entity\Project;
use VersionContol\GitControlBundle\Form\ProjectType;
use VersionContol\GitControlBundle\Utility\GitCommands;
use Symfony\Component\Validator\Constraints\NotBlank;
use VersionContol\GitControlBundle\Entity\UserProjects;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use VersionContol\GitControlBundle\Annotation\ProjectAccess;

/**
 * Project controller.
 *
 * @Route("/project/{id}/file")
 */
class ProjectFilesController extends BaseProjectController{
    
    /**
     *
     * @var GitCommand 
     */
    protected $gitFilesCommands;
    
    
    /**
     * Show Git commit diff
     *
     * @Route("s/{currentDir}",defaults={"$currentDir" = ""}, name="project_filelist")
     * @Method("GET")
     * @Template()
     * @ProjectAccess(grantType="VIEW")
     */
    public function fileListAction($id,$currentDir = ''){

        $dir = '';
        if($currentDir){
            $dir = trim(urldecode($currentDir));
        }
        $files = $this->gitFilesCommands->listFiles($dir, $this->branchName);
        
        $readme = '';
        foreach($files as $file){
            if(strtolower($file->getExtension()) == 'md' || strtolower($file->getExtension()) == 'markdown'){
                $readme = $this->gitFilesCommands->readFile($file);
                break;
            }
        }
   
        return array_merge($this->viewVariables, array(
            'files' => $files,
            'currentDir' => $dir,
            'readme' => $readme
        ));
    }
    
    
    /**
     * 
     * @param integer $id Project Id
     */
    public function initAction($id,$grantType = 'VIEW'){
        
        parent::initAction($id,$grantType);
        
        $this->gitFilesCommands = $this->gitCommands->command('files');

    }
    
    /**
     * Adds File to .gitignore and remove file git index.
     *
     * @Route("/ignore/{filePath}", name="project_fileignore")
     * @Method("GET")
     * @Template("VersionContolGitControlBundle:ProjectFiles:fileList.html.twig")
     * @ProjectAccess(grantType="MASTER")
     */
    public function ignoreAction($id,$filePath){
        //$this->initAction($id,'MASTER');
        
        $filePath = trim(urldecode($filePath));
        
        $response = $this->gitFilesCommands->ignoreFile($filePath, $this->branchName);
        
        $this->get('session')->getFlashBag()->add('notice', $response);
            
        return $this->redirect($this->generateUrl('project_filelist', array('id' => $id)));
    }
    
   
}
