<?php namespace Illuminate\Mail;

use Swift_Mailer;
use Illuminate\Support\ServiceProvider;
use Swift_SmtpTransport as SmtpTransport;

class MailServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerSwiftMailer();

		$this->app['mailer'] = $this->app->share(function($app)
		{
			// Once we have create the mailer instance, we will set a container instance
			// on the mailer. This allows us to resolve mailer classes via containers
			// for maximum testability on said classes instead of passing Closures.
			$mailer = new Mailer($app['view'], $app['swift.mailer']);

			$mailer->setLogger($app['log']);

			$mailer->setContainer($app);

			$from = $app['config']['mail.from'];

			// If a "from" address is set, we will set it on the mailer so that all mail
			// messages sent by the applications will utilize the same "from" address
			// on each one, which makes the developer's life a lot more convenient.
			if (is_array($from) and isset($from['address']))
			{
				$mailer->alwaysFrom($from['address'], $from['name']);
			}

			return $mailer;
		});
	}

	/**
	 * Register the Swift Mailer instance.
	 *
	 * @return void
	 */
	protected function registerSwiftMailer()
	{
		$config = $this->app['config']['mail'];

		$this->registerSwiftTransport($config);

		// Once we have the transporter registered, we will register the actual Swift
		// mailer instance, passing in the transport instances, which allows us to
		// override this transporter instances during app start-up if necessary.
		$this->app['swift.mailer'] = $this->app->share(function($app)
		{
			return new Swift_Mailer($app['swift.transport']);
		});
	}

	/**
	 * Register the Swift Transport instance.
	 *
	 * @param  array  $config
	 * @return void
	 */
	protected function registerSwiftTransport($config)
	{
		$this->app['swift.transport'] = $this->app->share(function($app) use ($config)
		{
			extract($config);

			// The Swift SMTP transport instance will allow us to use any SMTP backend
			// for delivering mail such as Sendgrid, Amazon SMS, or a custom server
			// a developer has available. We will just pass this configured host.
			$transport = SmtpTransport::newInstance($host, $port);

			if (isset($encryption))
			{
				$transport->setEncryption($encryption);
			}

			// Once we have the transport we will check for the presence of a username
			// and password. If we have it we will set the credentials on the Swift
			// transporter instance so that we'll properly authenticate delivery.
			if (isset($username))
			{
				$transport->setUsername($username);

				$transport->setPassword($password);
			}

			return $transport;
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('mailer', 'swift.mailer', 'swift.transport');
	}

}