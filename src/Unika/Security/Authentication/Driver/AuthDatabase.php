<?php
/**
 *	This file is part of the UnikaCMF project
 *	
 *	@license MIT
 *	@author Fajar Khairil <fajar.khairil@gmail.com>
 */

namespace Unika\Security\Authentication\Driver;

use Unika\Security\Authentication\AuthDriverInterface;
use Unika\Security\Authentication\AuthException;

class AuthDatabase implements AuthDriverInterface
{
	protected $app;
	protected $db;
	protected $throttle;
	protected $user = null; //cached user

	public function __construct(\Unika\Application $app,$throttle_guard = True)
	{
		$this->app = $app;
		$this->db = $this->app['database']->table( $this->app->config('auth.database.users_table') );
		$this->throttle = (boolean)$throttle_guard;
		$this->init();
	}

	protected function init()
	{
		$self = $this;
		$this->app['Illuminate.events']->listen('auth.success',function($auth,$credentials) use($self){
			$self->doOnSucess($auth,$credentials);
		});

		$this->app['Illuminate.events']->listen('auth.failure',function($credentials) use($self){
			$self->doOnFailure($credentials);
		});			
	}

	protected function doOnFailure($credentials)
	{
		$_sql = 'UPDATE '.$this->app->config('auth.database.users_table').
			' SET last_failed_count = last_failed_count + 1
			WHERE username = "'.$credentials['username'].'"';

		$this->app['database']->getConnection()->update($_sql);
	}

	/**
	 *
	 *	@return user or null if not found
	 */
	protected function resolveUser(array $credentials)
	{
		if( $this->user === null )
		{
			$this->user = $this->db->where('username','=',$credentials['username'])->first();
		}

		return $this->user;
	}

	/**
	 *
	 *	@return True on blocked , null if credential not found
	 */
	public function isBlocked(array $credentials)
	{
		$user = $this->resolveUser($credentials);
		
		if( $user )
		{
			if( (int)$user['last_failed_count'] >= (int)$this->app->config('auth.guard.throttling_count') )
			{
				$this->db->where('id' ,'=',$user['id'])->update(['active' => 0,'updated_at' =>  date('Y-m-d H:i:s')]);
				return True;
			}
			else
			{
				return False;
			}
		}

		return $user;
	}

	protected function doOnSucess($auth,$credentials)
	{
		$now = date('Y-m-d H:i:s');
		$this->db->where('username' ,'=',$credentials['username'])->update(['last_login' => $now,'updated_at' =>  $now]);
	}

	/**
	 *	@param array $credentials
	 *	@return AuthUserInteface
	 *	@throw AuthException
	 */
	public function authenticate(array $credentials)
	{
		$user = $this->resolveUser($credentials);

		if( !$user )
		{
			// @todo : should we localized it?
			throw new AuthException('Invalid Username supplied');
		}

		$credentials['salt'] = $user['salt'];

		$passwordLibClass = $this->app->config('auth.password_hasher_class');
	
		if( !\Unika\Util::classImplements($passwordLibClass,'Unika\Security\PasswordHasherInterface') )
		{
			throw new AuthException('invalid password_hasher_class please check your auth config.');
		}

		$passwordLib = new $passwordLibClass();

		$isValidPassword = $passwordLib->verifyPasswordHash( $credentials['password'].$user['salt'],$user['pass'] );

		if( !$isValidPassword )
		{
			throw new AuthException('invalid password supplied.');
		}

		return $user;
	}
}