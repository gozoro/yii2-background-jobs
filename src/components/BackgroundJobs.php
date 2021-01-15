<?php

namespace gozoro\background\models;

use Yii;
use gozoro\background\models\BackgroundJob;




/**
 * Component for working with background jobs.
 */
class BackgroundJobs extends \yii\base\Component
{
	/**
	 * The directory where the job files are created.
	 * @var string
	 */
	public $jobFolder = '@app/runtime/background-jobs';

	/**
	 * The console command to run background job.
	 * {$jobfile} will be replaced to actual path to the job-file.
	 * @var string
	 */
	public $command = '@app/yii job/run {$jobfile} > /dev/null 2>&1 &';


	/**
	 * Returns path to a directory where the job files are created.
	 * @return string
	 * @throws \yii\base\Exception
	 */
	public function getJobFolder()
	{
		return Yii::getAlias($this->jobFolder);
	}

	/**
	 * Returns filepath to job-file.
	 *
	 * @param string $jobId
	 * @return string
	 */
	public function createJobFilePath($jobId)
	{
		$folder  = $this->getJobFolder();
		return $folder.'/'.$jobId.'.job';
	}

	/**
	 * Runs the background job.
	 * Returns TRUE if the job was started successfully.
	 *
	 * @param BackgroundJob $job
	 * @return boolean
	 * @throws \yii\base\Exception
	 */
	public function run(BackgroundJob $job)
	{
		$folder  = $this->getJobFolder();

		if(!file_exists($folder))
		{
			throw new \yii\base\Exception("Folder $folder is not exists.");
		}

		$jobfile = $this->createJobFilePath($job->getId());
		$content = serialize($job);

		if(file_put_contents($jobfile, $content, LOCK_EX))
		{
			$command = str_replace('{$jobfile}', $jobfile, $this->command);
			$command = Yii::getAlias($command);

			if(system($command) === false)
			{
				throw new \yii\base\Exception("Failed run console command.");
			}

			return true;
		}
		else
		{
			throw new \yii\base\Exception("Failed create job file - $jobfile.");
		}
	}

	/**
	 * Returns TRUE if the background job file exists.
	 * @param string $jobId
	 * @return bool
	 */
	public function isExistsJob($jobId)
	{
		$jobfile = $this->createJobFilePath($jobId);
		return file_exists($jobfile);
	}

	/**
	 * Returns BackgroundJob object
	 *
	 * @param string $jobId the unique identifier of the job
	 * @return BackgroundJob
	 * @throws \yii\base\Exception
	 */
	public function getJob($jobId)
	{
		$jobfile = $this->createJobFilePath($jobId);

		if(file_exists($jobfile))
		{
			$content = file_get_contents($jobfile);

			if($content === false)
			{
				throw new \yii\base\Exception("Couldn't get content from file $jobfile.");
			}
			else
			{
				$job = unserialize($content);

				if($job)
				{
					if($job instanceof BackgroundJob)
					{
						return $job;
					}
					else
					{
						throw new \yii\base\Exception("Failed job class in $jobfile.");
					}
				}
				else
				{

				throw new \yii\base\Exception("Failed unserialize job file $jobfile.");
				}
			}
		}
		else
		{
			throw new \yii\base\Exception("Job file $jobfile not exists.");
		}
	}
}