<?php

namespace VersionContol\GitControlBundle\Controller;

use VersionContol\GitControlBundle\Controller\Base\BaseProjectController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use VersionContol\GitControlBundle\Entity\Project;
use VersionContol\GitControlBundle\Form\ProjectType;
use VersionContol\GitControlBundle\Utility\GitCommands;
use Symfony\Component\Validator\Constraints\NotBlank;
use VersionContol\GitControlBundle\Entity\UserProjects;
use VersionContol\GitControlBundle\Utility\GitCommands\GitCommand;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
 /** ///Route("/example", service="example_bundle.controller.example_controller") */

/**
 * Project controller.
 *
 * @Route("/branch")
 */
class ProjectBranchController extends BaseProjectController
{
    
    /**
     *
     * @var GitCommand 
     */
    protected $gitCommands;
    
    /**
     *
     * @var GitCommand 
     */
    protected $gitBranchCommands;
    
    /**
     * The current Project
     * @var Project 
     */
    protected $project;
    
    /**
     * List Branches. Not sure how to list remote and local branches.
     *
     * @Route("es/{id}", name="project_branches")
     * @Method("GET")
     * @Template()
     */
    public function branchesAction($id)
    {
        
        $this->initAction($id);
        
        
        $branchName = $this->gitBranchCommands->getCurrentBranch();
        //Remote Server choice 
        $gitLocalBranches = $this->gitBranchCommands->getBranches(true);
        /*$gitBranches = $gitLocalBranches;
        $gitAllBranches = $this->gitBranchCommands->getBranches();
        foreach ($gitAllBranches as $branch){
            if(strpos($branch,'/') !== false){
                $branchParts = explode('/',$branch);
                if(!in_array($branchParts[1], $gitLocalBranches)){
                    $gitBranches[] = $branch;
                }
            }
        }*/
        
        $gitLogs = $this->gitCommands->getLog(1,$branchName);

        $form = $this->createNewBranchForm($this->project);
        

        
        return array(
            'project'      => $this->project,
            'branches' => $gitLocalBranches,
            'branchName' => $branchName,
            'form' => $form->createView(),
            'gitLogs' => $gitLogs
        );
    }
    
    

    /**
     * Pulls git repository from remote to local.
     *
     * @Route("/create/{id}", name="project_branch")
     * @Method("POST")
     * @Template("VersionContolGitControlBundle:ProjectBranch:branches.html.twig")
     */
    public function createBranchAction(Request $request,$id)
    {
        $this->initAction($id);
        
        $form = $this->createNewBranchForm($this->project);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $newBranchName = $data['name'];
            $switchToBranch= $data['switch'];
            try{
                
                $response = $this->gitBranchCommands->createLocalBranch($newBranchName,$switchToBranch);
                $this->get('session')->getFlashBag()->add('notice', $response);
                return $this->redirect($this->generateUrl('project_branches', array('id' => $id)));
                
            }catch(\Exception $e){
               $this->get('session')->getFlashBag()->add('error', $e->getMessage()); 
            }
 
        }
        
        $branchName = $this->gitBranchCommands->getCurrentBranch();
        $gitLocalBranches = $this->gitBranchCommands->getBranches(true);
        $gitLogs = $this->gitCommands->getLog(1,$branchName);

        return array(
           'project'      => $this->project,
            'branches' => $gitLocalBranches,
            'branchName' => $branchName,
            'form' => $form->createView(),
            'gitLogs' => $gitLogs
        );
    }
    
    /**
     * Pulls git repository from remote to local.
     *
     * @Route("/checkoutbranch/{id}/{branchName}", name="project_checkoutbranch" , requirements={"branchName"=".+"})
     * @Method("GET")
     * @Template("VersionContolGitControlBundle:Project:branches.html.twig")
     */
    public function checkoutBranchAction($id, $branchName){
        
        $this->initAction($id);
        
        $response = $this->gitBranchCommands->checkoutBranch($branchName);
        
        $this->get('session')->getFlashBag()->add('notice', $response);
        
        return $this->redirect($this->generateUrl('project_branches', array('id' => $id)));
    }
    
    /**
     * List Branches. Not sure how to list remote and local branches.
     *
     * @Route("/remotes/{id}", name="project_branch_remotes")
     * @Method("GET")
     * @Template()
     */
    public function remoteBranchesAction($id)
    {
        
        $this->initAction($id);
        
        $branchName = $this->gitBranchCommands->getCurrentBranch();
        
        //Remote Server choice 
        $gitRemoteBranches = $this->gitBranchCommands->getBranchRemoteListing();
        
        $form = $this->createNewBranchForm($this->project,'project_branch_remote_checkout');
        $form->add('remotename', 'hidden', array(
                'label' => 'Remote Branch Name'
                ,'required' => true
                ,'constraints' => array(
                    new NotBlank()
                ))
            );  
        
        return array(
            'project'      => $this->project,
            'branches' => $gitRemoteBranches,
            'branchName' => $branchName,
            'form' => $form->createView(),
        );
    }
    
    /**
     * Pulls git repository from remote to local.
     *
     * @Route("/checkout-remote/{id}", name="project_branch_remote_checkout")
     * @Method("POST")
     * @Template("VersionContolGitControlBundle:ProjectBranch:remoteBranches.html.twig")
     */
    public function checkoutRemoteBranchAction(Request $request,$id)
    {

        $this->initAction($id);
        
        $branchName = $this->gitBranchCommands->getCurrentBranch();
         $gitRemoteBranches = $this->gitBranchCommands->getBranchRemoteListing();

        $form = $this->createNewBranchForm($this->project,'project_branch_remote_checkout');
        $form->add('remotename', 'hidden', array(
                'label' => 'Remote Branch Name'
                ,'required' => true
                ,'constraints' => array(
                    new NotBlank()
                ))
            );  
        
        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $newBranchName = $data['name'];
            $remoteBranchName = $data['remotename'];
            $switchToBranch= $data['switch'];
            
            try{
                $response = $this->gitBranchCommands->createBranchFromRemote($newBranchName,$remoteBranchName,$switchToBranch);
                $this->get('session')->getFlashBag()->add('notice', $response);
                return $this->redirect($this->generateUrl('project_branch_remotes', array('id' => $id)));
                
            }catch(\Exception $e){
               $this->get('session')->getFlashBag()->add('error', $e->getMessage()); 
            }
            
            
        }

        return array(
           'project'      => $this->project,
            'branches' => $gitRemoteBranches,
            'branchName' => $branchName,
            'form' => $form->createView(),
        );
    }
    
    /**
     * Pulls git repository from remote to local.
     *
     * @Route("/fetchall/{id}/", name="project_branch_fetchall")
     * @Method("GET")
     * @Template("VersionContolGitControlBundle:ProjectBranch:remoteBranches.html.twig")
     */
    public function fetchAllAction($id){
        
        $this->initAction($id);
        
        $response = $this->gitBranchCommands->fetchAll();
        
        $this->get('session')->getFlashBag()->add('notice', $response);
        
        return $this->redirect($this->generateUrl('project_branch_remotes', array('id' => $id)));
    }
    
    
    /**
     * Pulls git repository from remote to local.
     *
     * @Route("/deletebranch/{id}/{branchName}", name="project_deletebranch" , requirements={"branchName"=".+"})
     * @Method("GET")
     * @Template("VersionContolGitControlBundle:Project:branches.html.twig")
     */
    public function deleteBranchAction($id, $branchName){
        
        $this->initAction($id);
        
        $response = $this->gitBranchCommands->deleteBranch($branchName);
        
        $this->get('session')->getFlashBag()->add('notice', $response);
        
        return $this->redirect($this->generateUrl('project_branches', array('id' => $id)));
    }
    
    
    
    /**
    * Creates a form to edit a Project entity.
    *
    * @param Project $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createNewBranchForm($project,$formAction = 'project_branch')
    {

        $defaultData = array();
        $form = $this->createFormBuilder($defaultData, array(
                'action' => $this->generateUrl($formAction, array('id' => $project->getId())),
                'method' => 'POST',
            ))
            ->add('name', 'text', array(
                'label' => 'Branch Name'
                ,'required' => true
                ,'constraints' => array(
                    new NotBlank()
                ))
            )   
            ->add('switch', 'checkbox', array(
                'label' => 'Switch to branch on creation'
                ,'required' => false
                )
            )   
            ->getForm();

        $form->add('submit', 'submit', array('label' => 'Create'));
        return $form;
    }
    
    /**
     * 
     * @param integer $id
     */
    protected function initAction($id){
 
        $em = $this->getDoctrine()->getManager();

        $this->project= $em->getRepository('VersionContolGitControlBundle:Project')->find($id);

        if (!$this->project) {
            throw $this->createNotFoundException('Unable to find Project entity.');
        }
        $this->checkProjectAuthorization($this->project,'EDIT');
        
        $this->gitCommands = $this->get('version_control.git_command')->setProject($this->project);
        $this->gitBranchCommands = $this->get('version_control.git_branch')->setProject($this->project);
    }
    
    
}

