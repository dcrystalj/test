<?php namespace Illuminate\Queue;

class SyncQueue extends Queue implements QueueInterface {

	/**
	 * Push a new job onto the queue.
	 *
	 * @param  string  $job
	 * @param  mixed   $data
	 * @param  string  $queue
	 * @return void
	 */
	public function push($job, $data = '', $queue = null)
	{
		$this->resolveJob($job, $data)->fire();
	}

	/**
	 * Push a new job onto the queue after a delay.
	 *
	 * @param  int     $delay
	 * @param  string  $job
	 * @param  mixed   $data
	 * @param  string  $queue
	 * @return void
	 */
	public function later($delay, $job, $data = '', $queue = null)
	{
		return $this->push($job, $data, $queue);
	}

	/**
	 * Pop the next job off of the queue.
	 *
	 * @param  string  $queue
	 * @return Illuminate\Queue\Jobs\Job|null
	 */
	public function pop($queue = null) {}

	/**
	 * Resolve a Sync job instance.
	 *
	 * @param  string  $job
	 * @param  string  $data
	 * @return Illuminate\Queue\Jobs\SyncJob
	 */
	protected function resolveJob($job, $data)
	{
		return new Jobs\SyncJob($this->container, $job, $data);
	}

}