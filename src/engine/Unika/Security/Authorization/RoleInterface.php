<?php
/**
 *	This file is part of the Unika-CMF project.
 *	Role Interface
 *	
 *	@license MIT
 *	@author Fajar Khairil
 */

namespace Unika\Security\Authorization;

Interface RoleInterface{

	public function getRoleId();

	public function getRoleName();

	public function getRoleDescription();
} 