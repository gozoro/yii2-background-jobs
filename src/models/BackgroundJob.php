<?php

namespace gozoro\background\models;


use Yii;

/**
 * Abstract background job.
 */
abstract class BackgroundJob
{
	const STATUS_NEW     = 'new';
	const STATUS_RUNNING = 'running';
	const STATUS_ERROR   = 'error';
	const STATUS_SUCCESS = 'success';




	private $_id;
	private $_status = self::STATUS_NEW;
	private $_createTs;
	private $_runTs;
	private $_finishTs;
	private $_errorMessage;





	public function __construct(array $params = [])
	{
		Yii::configure($this, $params);

		$this->_id = md5(uniqid());
		$this->_createTs = time();
		$this->init();
	}


	public function init()
	{

	}

	/**
	 * Returns the unique identifier of the job
	 * @return string
	 */
	public function getId()
	{
		return $this->_id;
	}

	public function getCreateDate($format = 'Y-m-d H:i:s')
	{
		return date($format, $this->_createTs);
	}


	public function getRunDate($format = 'Y-m-d H:i:s')
	{
		if($this->_runTs)
		{
			return date($format, $this->_runTs);
		}
		else
			return null;
	}

	public function getFinishDate($format = 'Y-m-d H:i:s')
	{
		if($this->_finishTs)
		{
			return date($format, $this->_finishTs);
		}
		else
			return null;
	}

	public function getStatus()
	{
		return $this->_status;
	}

	public function getErrorMessage()
	{
		return $this->_errorMessage;
	}



	public function beginJob($jobfile)
	{
		$this->_status = self::STATUS_RUNNING;
		$this->_runTs = time();

		file_put_contents($jobfile, serialize($this), LOCK_EX);
	}

	public function endJob($jobfile, $isSuccess=true, $errorMessage=null)
	{
		$this->_finishTs = time();

		if($isSuccess)
		{
			$this->_status = self::STATUS_SUCCESS;
		}
		else
		{
			$this->_status = self::STATUS_ERROR;
			$this->_errorMessage = $errorMessage;
		}

		file_put_contents($jobfile, serialize($this), LOCK_EX);
	}





	abstract public function run();

}