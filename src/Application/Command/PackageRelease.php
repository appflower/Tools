<?php
namespace Application\Command;
use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;
 
/**
 * Command that cretes ready to use packages with given version of studio playground
 * It creates four archive files.
 * Base project with .git directory included and without any submodules (as tgz and zip)
 * Base project with all submodules cloned and without .git dirs (as tgz and zip)
 * 
 * This command uses mkdir, git, tar, zip binaries so - they should be in place.
 * It is rather linux only command because of that of course.
 * 
 * @todo - probably we will want to make this command more flexible - this should be easy
 *         we just need to allow to provide more parameters passed in to command
 * @todo - while cloning submodules we should also provide support for nested submodules
 *         when we switch to sfPropelORMPlugin this will be needed
 */
class PackageRelease extends Console\Command\Command
{
    
    /**
     * Temporary directory that we will be working in
     * @var string
     */
    private $tmpDir;
    /**
     * Project repository we will use to build a release.
     * It should be read-only URL
     * @var string
     */
    private $repositoryURL = 'git://github.com/appflower/appflower_studio_playground.git';
    /**
     * Directory name of a project - it will be included inside the archives
     * @var string
     */
    private $playgroundDirName = 'appflower_studio_playground';
    
    
    protected function configure() {
        $this
        ->setName('release:package')
        ->addArgument('tag', InputArgument::REQUIRED, 'Git tag name that release should be based on')
        ->setDescription('Prepares downloadable archives (tgz, zip) with given GIT project')
        ->setHelp(sprintf(
                '%sBuilds downloadable archives (tgz, zip) from given git project%s',
                PHP_EOL,
                PHP_EOL
        ));
    }
 
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->tmpDir = sys_get_temp_dir().'/AFTools-'.time().'/';
        
        if (file_exists($this->tmpDir)) {
            throw new Exception('Something is wrong. Temporary path already exists while it should not :|: '.$this->tmpDir);
        }
        
        $this->runCommand("mkdir -p $this->tmpDir");
        
        $tag = $input->getArgument('tag');
        
        $this->tmpPlaygroundDir = $this->tmpDir.$this->playgroundDirName;

        $output->writeln("Cloning project to temporary location");
        $this->runCommand("git clone $this->repositoryURL $this->tmpPlaygroundDir");
 
        $output->writeln("Changing dir to $this->tmpPlaygroundDir");
        chdir($this->tmpPlaygroundDir);
        $output->writeln("Checking out $tag");
        $this->runCommand("git checkout ".$tag);
        $output->writeln("Changing dir to $this->tmpDir");
        chdir($this->tmpDir);
        
        $this->createArchives($output, $this->playgroundDirName.'-'.$tag);
        
        $output->writeln("Changing dir to $this->tmpPlaygroundDir");
        chdir($this->tmpPlaygroundDir);
        $output->writeln("Updating git submodules");
        $this->runCommand('git submodule init');
        $this->runCommand('git submodule update');
        
        $this->cleanGitDirectories($output);

        $output->writeln("Changing dir to $this->tmpDir");
        chdir($this->tmpDir);

        $this->createArchives($output, $this->playgroundDirName.'_vendors-'.$tag);
        
        $output->writeln('Everything went fine :) - You can find your packages inside: '.$this->tmpDir);
    }

    /**
     * Executes passed $command using exec() and throws Exception on any error
     * @param string $command
     * @return void
     * @throws Exception
     */
    protected function runCommand($command)
    {
        exec($command . ' 2>&1', $execOutput, $returnValue);
        
        if ($returnValue !== 0) {
            throw new Exception("Command failed: '$command', with:\n".join("\n", $execOutput));
        }
    }
    
    /**
     * This one adds interactivity to our command. It looks for .git directories
     * that are candidates to deletion and asks user if it is ok to delete them.
     * Potencially we could do this automatically but it si "rm -rf SOMETHING" so
     * just to be sure we ask for deletion permission.
     * 
     * @param OutputInterface $output 
     */
    private function cleanGitDirectories(OutputInterface $output)
    {
        $output->writeln("Looking for .git directories to delete");
        exec('find . -name \'.git\'', $commandOutput);
        
        if (count($commandOutput) < 1) {
            $output->writeln('None found - strange :|.');
        }
        
        $formatter = $this->getHelperSet()->get('formatter');
        $output->write(
            $formatter->formatBlock(
                "I would like to delete those directories from inside '$this->tmpPlaygroundDir' :".PHP_EOL.implode(PHP_EOL, $commandOutput).PHP_EOL,
                'comment'
            )
        );
        
        $dialog = $this->getHelperSet()->get('dialog');
        $answer = $dialog->ask($output, 'Can I (yes/NO)? ');
        
        if (strtolower($answer) === 'yes') {
            $output->writeln('Deleting directories');
            foreach ($commandOutput as $dirToRemove) {
                exec("rm -rf $dirToRemove");
            }
        }
    }
    
    /**
     * Create archies using tar and zip binaries
     * 
     * @param OutputInterface $output
     * @param string $baseArchiveName 
     */
    private function createArchives(OutputInterface $output, $baseArchiveName)
    {
        $output->writeln("Creating tgz {$baseArchiveName} archive");
        $this->runCommand("tar zcvf {$baseArchiveName}.tgz $this->playgroundDirName");
        $output->writeln("Creating zip {$baseArchiveName} archive");
        $this->runCommand("zip -ry9 {$baseArchiveName}.zip $this->playgroundDirName");
    }
}
?>
