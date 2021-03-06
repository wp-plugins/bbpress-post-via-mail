<?php

/**
 * A new way of using the WordPress API
 */
abstract class pvm_Autohooker {

	/**
	 * Register hooks
	 *
	 * @see Sputnik_Library_Plugin::register_hooks
	 * @see Sputnik_Library_Plugin_Dynamic::register_hooks
	 * @param boolean|array $prefixes True for default (`action_`/`filter_`), array with keys "action" & "filter" or false
	 */
	public static function register_hooks($prefixes = false) {
		$parent = get_called_class();
		$is_sane = self::check_eaccelerator_saneness();
		if (!$is_sane) {
			// This will be replaced with something better soon
			throw new Exception('eAccelerator is configured to strip doc comments, cannot continue');
		}

		$enable_prefixes = true;
		if ($prefixes === false) {
			$enable_prefixes = false;
		}
		elseif ($prefixes === true) {
			$prefixes = array('filter' => 'filter_', 'action' => 'action_');
		}

		$self = new ReflectionClass($parent);
		foreach ($self->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
			$params = $method->getNumberOfParameters();
			$doc = $method->getDocComment();
			if (!empty($doc) && preg_match('#^\s+\*\s*@wp-nohook#im', $doc) !== 0) {
				continue;
			}

			$hooks = array('filter' => array(), 'action' => array());

			if ($enable_prefixes === true) {
				// If either prefix is blank, always hook
				if ($prefixes['filter'] === '' || $prefixes['action'] === '') {
					$hooks['filter'][$method->name] = 10;
				}

				// Method starts with filter prefix
				elseif ($enable_prefixes === true && strpos($method->name, $prefixes['filter']) === 0) {
					$hook = substr($method->name, strlen($prefixes['filter']));
					$hooks['action'][$hook] = 10;
				}

				// Method starts with action prefix
				elseif ($enable_prefixes === true && strpos($method->name, $prefixes['action']) === 0) {
					$hook = substr($method->name, strlen($prefixes['action']));
					$hooks['action'][$hook] = 10;
				}
			}

			// If we haven't hooked anything yet, check phpdoc
			if (empty($hooks['filter']) && empty($hooks['action'])) {
				if (empty($doc) || (strpos($doc, '@wp-filter') === false && strpos($doc, '@wp-action') === false)) {
					continue;
				}

				preg_match_all('#^\s+\*\s*@wp-(action|filter)\s+([\w-]+)(\s*\d+)?#im', $doc, $matches, PREG_SET_ORDER);
				if (empty($matches)) {
					continue;
				}
				foreach ($matches as $match) {
					$type = $match[1];
					$hook = $match[2];
					$priority = 10;
					if (!empty($match[3])) {
						$priority = (int) $match[3];
					}

					$hooks[$type][$hook] = $priority;
				}
			}

			foreach ($hooks['filter'] as $hook => $priority) {
				call_user_func(array($parent, 'add_filter'), $hook, $method->name, $priority, $params, $parent);
			}
			foreach ($hooks['action'] as $hook => $priority) {
				call_user_func(array($parent, 'add_action'), $hook, $method->name, $priority, $params, $parent);
			}
		}
	}

	/**
	 * Check if eAccelerator is loaded, and if so, is sane
	 *
	 * @internal If you can find me, it's sane.
	 * @return boolean True if doc comments are fine, false otherwise
	 */
	private static function check_eaccelerator_saneness() {
		if (!extension_loaded('eaccelerator') && !extension_loaded('eAccelerator')) {
			return true;
		}

		$method = new ReflectionMethod('pvm_Autohooker', 'check_eaccelerator_saneness');
		$comment = $method->getDocComment();

		return (strpos($comment, "If you can find me, it's sane.") !== false);
	}

	/**
	 * Add a method as a filter
	 *
	 * This is exactly the same as {@see add_filter()} but instead of passing
	 * a full callback, only the method needs to be passed in.
	 *
	 * @param string $hook Filter name
	 * @param string $method Method name on current class, or priority (as an int)
	 * @param int $priority Specify the order in which the functions associated with a particular action are executed (default: 10)
	 * @param int $accepted_args Number of parameters which callback accepts (default: corresponds to method prototype)
	 * @param string $class Internal use only
	 */
	protected static function add_filter($hook, $method = null, $priority = 10, $params = null, $class = null) {
		if ($method === null) {
			$method = $hook;
		}
		elseif (is_int($method)) {
			$priority = $method;
			$method = $hook;
		}

		if ($class === null) {
			$class = get_called_class();
		}

		if (!method_exists($class, $method)) {
			throw new InvalidArgumentException('Method does not exist');
		}

		if ($params === null) {
			$ref = new ReflectionMethod($class, $method);
			$params = $ref->getNumberOfParameters();
		}

		return add_filter($hook, array($class, $method), $priority, $params);
	}

	/**
	 * Add a method as a action
	 *
	 * This is exactly the same as {@see add_action()} but instead of passing
	 * a full callback, only the method needs to be passed in.
	 *
	 * @internal This is duplication, but ensures consistency with WordPress API
	 * @param string $hook Action name
	 * @param string $method Method name on current class, or priority (as an int)
	 * @param int $priority Specify the order in which the functions associated with a particular action are executed (default: 10)
	 * @param int $accepted_args Number of parameters which callback accepts (default: corresponds to method prototype)
	 * @param string $class Internal use only
	 */
	protected static function add_action($hook, $method = null, $priority = 10, $params = null, $class = null) {
		if ($method === null) {
			$method = $hook;
		}
		elseif (is_int($method)) {
			$priority = $method;
			$method = $hook;
		}

		if ($class === null) {
			$class = get_called_class();
		}

		if (!method_exists($class, $method)) {
			throw new InvalidArgumentException('Method does not exist');
		}

		if ($params === null) {
			$ref = new ReflectionMethod($class, $method);
			$params = $ref->getNumberOfParameters();
		}

		return add_action($hook, array($class, $method), $priority, $params);
	}
}
