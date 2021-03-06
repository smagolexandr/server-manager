<?php

namespace AppBundle\Command;

use AppBundle\Entity\Site;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckSiteStatusCommand extends Command
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Registry $doctrine, LoggerInterface $logger)
    {
        parent::__construct();

        $this->em = $doctrine->getManager();
        $this->logger = $logger;
    }

    public function configure()
    {
        $this
            ->setName('app:check-site-status')
            ->setDescription('Checks the supported sites for expiration of SLA.')
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sites = $this->em->getRepository(Site::class)->findBy(['status' => Site::STATUS_SUPPORTED]);
        if (!$sites) {
            $this->logger->info('No sites to update.');
            return;
        }

        foreach ($sites as $site) {
            if ($site->getSlaEndAt() && $site->getSlaEndAt()  < new \DateTime()) {
                $site->setStatus(Site::STATUS_UNSUPPORTED);
                $this->logger->info($site->getName() . " updated");
            }
        }

        $this->em->flush();
    }
}
