<?php

namespace VersionContol\GitControlBundle\Utility\GitCommands;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use VersionContol\GitControlBundle\Entity\Project;
use VersionContol\GitControlBundle\Utility\SshProcess;
use VersionContol\GitControlBundle\Utility\ProjectEnvironmentStorage;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use VersionContol\GitControlBundle\Event\GitAlterFilesEvent;

abstract class GitCommand {
    
    protected $gitPath;
    
    /**
     * @var TokenStorage
     */
    protected $securityContext;
    
    /**
     * The git projectEnvironment entity
     * @var Project
     */
    protected $projectEnvironment;
    
    /**
     *
     * @var type Git Status Hash.
     * Used to make sure no changes has occurred since last check 
     * @var string hash
     */
    protected $statusHash;
    
    /**
     * @var ProjectEnvironmentStorage
     */
    protected $projectEnvironmentStorage;
    
    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $dispatcher;
    
    /**
     * Get current active Branch Name
     * If there is no commits (eg new repo) then branch name is 'NEW REPO'
     * This git command needs at least one commit before if show the correct branch name.
     *  
     * @return string The current branch name
     */
    public function getCurrentBranch(){
        $branchName = '';
        try{
            //$branchName =  $this->runCommand('git rev-parse --abbrev-ref HEAD');
            $branchName =  $this->runCommand('git symbolic-ref --short -q HEAD');
        }catch(\RuntimeException $e){
            if($this->getObjectCount() == 0){
                $branchName = 'NEW REPO';
            }
        }
        
        return $branchName;
        

    }
    
    /**
     * Wrapper function to run shell commands. Supports local and remote commands
     * depending on the projectEnvironment details
     * 
     * @param string $command command to run
     * @return string Result of command
     * @throws \RuntimeException
     */
    protected function runCommand($command){
        
        if($this->projectEnvironment->getSsh() === true){
            $fullCommand = sprintf('cd %s && %s',$this->gitPath,$command);
            $sshProcess = new SshProcess();
            $sshProcess->run(array($fullCommand),$this->projectEnvironment->getHost(),$this->projectEnvironment->getUsername(),22,$this->projectEnvironment->getPassword());
            return $sshProcess->getStdout();
        }else{
            if(is_array($command)){
                //$finalCommands = array_merge(array('cd',$this->gitPath,'&&'),$command);
                $builder = new ProcessBuilder($command);
                $builder->setPrefix('cd '.$this->gitPath.' && ');
                $process = $builder->getProcess();
                
            }else{
                $fullCommand = sprintf('cd %s && %s',$this->gitPath,$command);
                $process = new Process($fullCommand);
            }
            //return exec($fullCommand);
            //print_r($process->getCommandLine());
            $process->run();

            // executes after the command finishes
            if (!$process->isSuccessful()) {
                if(trim($process->getErrorOutput()) !== ''){
                    throw new \RuntimeException($process->getErrorOutput());
                }else{
                    //Git returns a false with a reponse. So return as if successfull
                    return $process->getOutput();
                }
            }
            
            return $process->getOutput();
        }
    }
    
    
    /**
     * Gets the git path
     * @return type
     */
    public function getGitPath() {
        return $this->gitPath;
    }
    
    /**
     * Sets the git path. 
     * @param string $gitPath
     * @return \VersionContol\GitControlBundle\Utility\GitCommands
     */
    protected function setGitPath($gitPath) {
        $this->gitPath = rtrim(trim($gitPath),'/');
        return $this;
    }

    /**
     * Sets the project entity
     * @param Project $project
     */
    public function setProject(Project $project) {
        
        $this->projectEnvironment = $this->projectEnvironmentStorage->getProjectEnviromment($project);
        $this->setGitPath($this->projectEnvironment->getPath());
        return $this;
    }
    
    /**
     * Splits a block of text on newlines and returns an array
     *  
     * @param string $text Text to split
     * @param boolean $trimSpaces If true then each line is trimmed of white spaces. Default true. 
     * @return array Array of lines
     */
    protected function splitOnNewLine($text,$trimSpaces = true){
        if(!trim($text)){
            return array();
        }
        $lines = preg_split('/$\R?^/m', $text);
        if($trimSpaces){
            return array_map(array($this,'trimSpaces'),$lines); 
        }else{
            return $lines; 
        }
    }
    
    public function trimSpaces($value){
        return trim(trim($value),'\'');
    }
    
    public function getSecurityContext() {
        return $this->securityContext;
    }

    public function getProjectEnvironmentStorage() {
        return $this->projectEnvironmentStorage;
    }

    public function setSecurityContext(TokenStorage $securityContext) {
        $this->securityContext = $securityContext;

    }

    public function setProjectEnvironmentStorage(ProjectEnvironmentStorage $projectEnvironmentStorage) {
        $this->projectEnvironmentStorage = $projectEnvironmentStorage;
    }

    public function getDispatcher() {
        return $this->dispatcher;
    }

    public function setDispatcher(\Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher) {
        $this->dispatcher = $dispatcher;
        return $this;
    }

    public function triggerGitAlterFilesEvent($eventName = 'git.alter_files'){
        $event = new GitAlterFilesEvent($this->projectEnvironment,array());
        $this->dispatcher->dispatch($eventName, $event);
    }

    
}