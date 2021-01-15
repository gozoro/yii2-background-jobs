<?php
namespace gozoro\background\commands;


use Yii;
use gozoro\background\models\BackgroundJob;

/**
 * Abstract job controller.
 * Use command "./yii job/run $jobfile &" for execute your background job.
 *
 */
abstract class JobController extends \yii\console\Controller
{
	public function actionRun($jobfile)
	{
		print "Start backround job: $jobfile\n";

		if(!file_exists($jobfile))
		{
			throw new \yii\console\Exception("The job-file $jobfile does not exist.");
		}


		$content = file_get_contents($jobfile);

		if($content === false)
		{
			throw new \yii\console\Exception("Couldn't get content from file $jobfile.");
		}


		$job = unserialize($content); /* @var $job BackgroundJob */

		if($job)
		{
			if($job instanceof BackgroundJob)
			{
				if($job->getStatus() == BackgroundJob::STATUS_NEW)
				{
					$job->beginJob($jobfile);

					try
					{
						if($job->run())
						{
							$job->endJob($jobfile, true);
						}
						else
						{
							$job->endJob($jobfile, false, "The job failed run.");
						}
					}
					catch(\Exception $e)
					{
						$job->endJob($jobfile, false, $e->getMessage());
					}
				}
				else
				{
					throw new \yii\console\Exception("The job has no STATUS_NEW.");
				}
			}
			else
			{
				throw new \yii\console\Exception("The job instance is not BackgroundJob.");
			}
		}
		else
		{
			throw new \yii\console\Exception("Failed unserialize job $jobfile.");
		}
	}
}