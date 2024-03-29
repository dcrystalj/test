<?php namespace Illuminate\Html;

use Illuminate\Routing\UrlGenerator;
use Illuminate\Html\HtmlBuilder as Html;
use Illuminate\Session\Store as Session;

class FormBuilder {

	/**
	 * The URL generator instance.
	 *
	 * @var Illuminate\Routing\UrlGenerator  $url
	 */
	protected $url;

	/**
	 * The CSRF token used by the form builder.
	 *
	 * @var string
	 */
	protected $csrfToken;

	/**
	 * The session store implementation.
	 *
	 * @var Illuminate\Support\Contracts\SessionStoreInterface
	 */
	protected $session;

	/**
	 * The current model instance for the form.
	 *
	 * @var mixed
	 */
	protected $model;

	/**
	 * An array of label names we've created.
	 *
	 * @var array
	 */
	protected $labels = array();

	/**
	 * The registered form builder macros.
	 *
	 * @var array
	 */
	protected $macros = array();

	/**
	 * The reserved form open attributes.
	 *
	 * @var array
	 */
	protected $reserved = array('method', 'url', 'route', 'action', 'files');

	/**
	 * Create a new form builder instance.
	 *
	 * @param  Illuminate\Routing\UrlGenerator  $url
	 * @param  string  $csrfToken
	 * @return void
	 */
	public function __construct(UrlGenerator $url, $csrfToken)
	{
		$this->url = $url;
		$this->csrfToken = $csrfToken;
	}

	/**
	 * Open up a new HTML form.
	 *
	 * @param  array   $options
	 * @return string
	 */
	public function open(array $options = array())
	{
		$method = array_get($options, 'method', 'post');

		// We need to extract the proper method from the attributes. If the method is
		// something other than GET or POST we'll use POST since we will spoof the
		// actual method since forms don't support PUT or DELETE as native HTML.
		$attributes['method'] = $this->getMethod($method);

		$attributes['action'] = $this->getAction($options);

		$attributes['accept-charset'] = 'UTF-8';

		// If the method is PUT or DELETE, we will need to add a spoofer hidden field
		// that will instruct this Symfony request to pretend that the method is a
		// different method than it actually is, for convenience from the forms.
		$append = $this->getAppendage($method);

		if (isset($options['files']) and $options['files'])
		{
			$options['enctype'] = 'multipart/form-data';
		}

		// Finally we're ready to create the final form HTML field. We will attribute
		// format the array of attributes. We will also add on the appendage which
		// is used to spoof the requests for PUT and DELETE requests to the app.
		$attributes = array_merge(

			$attributes, array_except($options, $this->reserved)

		);

		// Finally, we will concatenate all of the attributes into a single string so
		// we can build out the final form open statement. We'll also append on an
		// extra value for the hidden _method field if it's needed for the form.
		$attributes = Html::attributes($attributes);

		return '<form'.$attributes.'>'.$append;
	}

	/**
	 * Create a new model based form builder.
	 *
	 * @param  mixed  $model
	 * @param  array  $options
	 * @return string
	 */
	public function model($model, array $options = array())
	{
		$this->model = $model;

		return $this->open($options);
	}

	/**
	 * Close the current form.
	 *
	 * @return string
	 */
	public function close()
	{
		$this->labels = array();

		$this->model = null;

		return '</form>';
	}

	/**
	 * Generate a hidden field with the current CSRF token.
	 *
	 * @return string
	 */
	public function token()
	{
		return $this->hidden('_token', $this->csrfToken);
	}

	/**
	 * Create a form label element.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $options
	 * @return string
	 */
	public function label($name, $value, $options = array())
	{
		$this->labels[] = $name;

		$options = Html::attributes($options);

		return '<label for="'.$name.'"'.$options.'>'.e($value).'</label>';
	}

	/**
	 * Create a form input field.
	 *
	 * @param  string  $type
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $options
	 * @return string
	 */
	public function input($type, $name, $value = null, $options = array())
	{
		$options['name'] = $name;

		// We will get the appropriate value for the given field. We will look for the
		// value in the session for the value in the old input data then we'll look
		// in the model instance if one is set. Otherwise we will just use empty.
		$id = $this->getIdAttribute($name, $options);

		$value = $this->getValueAttribute($name, $value);

		$merge = compact('type', 'value', 'id');

		// Once we have the type, value, and ID we can marge them into the rest of the
		// attributes array so we can convert them into their HTML attribute format
		// when creating the HTML element. Then, we will return the entire input.
		$options = array_merge($options, $merge);

		return '<input'.Html::attributes($options).'>';
	}

	/**
	 * Create a text input field.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $options
	 * @return string
	 */
	public function text($name, $value = null, $options = array())
	{
		return $this->input('text', $name, $value, $options);
	}

	/**
	 * Create a password input field.
	 *
	 * @param  string  $name
	 * @param  array   $options
	 * @return string
	 */
	public function password($name, $options = array())
	{
		return $this->input('password', $name, '', $options);
	}

	/**
	 * Create a hidden input field.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $options
	 * @return string
	 */
	public function hidden($name, $value = null, $options = array())
	{
		return $this->input('hidden', $name, $value, $options);
	}

	/**
	 * Create an e-mail input field.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $options
	 * @return string
	 */
	public function email($name, $value = null, $options = array())
	{
		return $this->input('email', $name, $value, $options);
	}

	/**
	 * Create a file input field.
	 *
	 * @param  string  $name
	 * @param  array   $options
	 * @return string
	 */
	public function file($name, $options = array())
	{
		return $this->input('file', $name, null, $options);
	}

	/**
	 * Create a textarea input field.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $options
	 * @return string
	 */
	public function textarea($name, $value = null, $options = array())
	{
		$options['name'] = $name;

		// Next we will look for the rows and cols attributes, as each of these are put
		// on the textarea element definition. If they are not present, we will just
		// assume some sane default values for these attributes for the developer.
		$options = $this->setTextAreaSize($options);

		$options['id'] = $this->getIdAttribute($name, $options);

		$value = (string) $this->getValueAttribute($name, $value);

		unset($options['size']);

		// Next we will convert the attributes into a string form. Also we have removed
		// the size attribute, as it was merely a short-cut for the rows and cols on
		// the element. Then we'll create the final textarea elements HTML for us.
		$options = Html::attributes($options);

		return '<textarea'.$options.'>'.e($value).'</textarea>';
	}

	/**
	 * Set the text area size on the attributes.
	 *
	 * @param  array  $options
	 * @return array
	 */
	protected function setTextAreaSize($options)
	{
		if (isset($options['size']))
		{
			return $this->setQuickTextAreaSize($options);
		}

		// If the "size" attribute was not specified, we will just look for the regular
		// columns and rows attributes, using sane defaults if these do not exist on
		// the attributes array. We'll then return this entire options array back.
		$cols = array_get($options, 'cols', 50);

		$rows = array_get($options, 'rows', 10);

		return array_merge($options, compact('cols', 'rows'));
	}

	/**
	 * Set the text area size using the quick "size" attribute.
	 *
	 * @param  array  $options
	 * @return array
	 */
	protected function setQuickTextAreaSize($options)
	{
		$segments = explode('x', $options['size']);

		return array_merge($options, array('cols' => $segments[0], 'rows' => $segments[1]));
	}

	/**
	 * Create a select box field.
	 *
	 * @param  string  $name
	 * @param  array   $list
	 * @param  string  $selected
	 * @param  array   $options
	 * @return string
	 */
	public function select($name, $list = array(), $selected = null, $options = array())
	{
		// When building a select box the "value" attribute is really the selected one
		// so we will use that when checking the model or session for a value which
		// should provide a convenient method of re-populating the forms on post.
		$selected = $this->getValueAttribute($name, $selected);

		$options['id'] = $this->getIdAttribute($name, $options);

		$options['name'] = $name;

		// We will simply loop through the options and build an HTML value for each of
		// them until we have an array of HTML declarations. Then we will join them
		// all together into one single HTML element that can be put on the form.
		$html = array();

		foreach ($list as $value => $display)
		{
			$html[] = $this->getSelectOption($display, $value, $selected);
		}

		// Once we have all of this HTML, we can join this into a single element after
		// formatting the attributes into an HTML "attributes" string, then we will
		// build out a final select statement, which will contain all the values.
		$options = Html::attributes($options);

		$list = implode('', $html);

		return "<select{$options}>{$list}</select>";
	}

	/**
	 * Get the select option for the given value.
	 *
	 * @param  string  $display
	 * @param  string  $value
	 * @param  string  $selected
	 * @return string
	 */
	protected function getSelectOption($display, $value, $selected)
	{
		if (is_array($display))
		{
			return $this->optionGroup($display, $value, $selected);
		}

		return $this->option($display, $value, $selected);
	}

	/**
	 * Create an option group form element.
	 *
	 * @param  array   $list
	 * @param  string  $label
	 * @param  string  $selected
	 * @return string
	 */
	protected function optionGroup($list, $label, $selected)
	{
		$html = array();

		foreach ($list as $value => $display)
		{
			$html[] = $this->option($display, $value, $selected);
		}

		return '<optgroup label="'.e($label).'">'.implode('', $html).'</optgroup>';
	}

	/**
	 * Create a select element option.
	 *
	 * @param  string  $display
	 * @param  string  $value
	 * @param  string  $selected
	 * @return string
	 */
	protected function option($display, $value, $selected)
	{
		$selected = $this->getSelectedValue($value, $selected);

		$options = array('value' => e($value), 'selected' => $selected);

		return '<option'.Html::attributes($options).'>'.e($display).'</option>';
	}

	/**
	 * Determine if the value is selected.
	 *
	 * @param  string  $value
	 * @param  string  $selected
	 * @return string
	 */
	protected function getSelectedValue($value, $selected)
	{
		if (is_array($selected))
		{
			return in_array($value, $selected) ? 'selected' : null;
		}

		return ((string) $value == (string) $selected) ? 'selected' : null;
	}

	/**
	 * Create a checkbox input field.
	 *
	 * @param  string  $name
	 * @param  mixed   $value
	 * @param  bool    $checked
	 * @param  array   $options
	 * @return string
	 */
	public function checkbox($name, $value = 1, $checked = null, $options = array())
	{
		return $this->checkable('checkbox', $name, $value, $checked, $options);
	}

	/**
	 * Create a radio button input field.
	 *
	 * @param  string  $name
	 * @param  mixed   $value
	 * @param  bool    $checked
	 * @param  array   $options
	 * @return string
	 */
	public function radio($name, $value = null, $checked = null, $options = array())
	{
		if (is_null($value)) $value = $name;

		return $this->checkable('radio', $name, $value, $checked, $options);
	}

	/**
	 * Create a checkable input field.
	 *
	 * @param  string  $type
	 * @param  string  $name
	 * @param  mixed   $value
	 * @param  bool    $checked
	 * @param  array   $options
	 * @return string
	 */
	protected function checkable($type, $name, $value, $checked, $options)
	{
		if (is_null($checked)) $checked = (bool) $this->getValueAttribute($name, null);

		if ($checked) $options['checked'] = 'checked';

		return $this->input($type, $name, $value, $options);
	}

	/**
	 * Create a submit button element.
	 *
	 * @param  string  $value
	 * @param  array   $options
	 * @return string
	 */
	public function submit($value = 'Submit', $options = array())
	{
		return $this->input('submit', null, $value, $options);
	}

	/**
	 * Create a button element.
	 *
	 * @param  string  $value
	 * @param  array   $options
	 * @return string
	 */
	public function button($value = null, $options = array())
	{
		return '<button'.Html::attributes($options).'>'.e($value).'</button>';
	}

	/**
	 * Register a custom form macro.
	 *
	 * @param  string    $name
	 * @param  callable  $macro
	 * @return void
	 */
	public function macro($name, $macro)
	{
		$this->macros[$name] = $macro;
	}

	/**
	 * Parse the form action method.
	 *
	 * @param  string  $method
	 * @return string
	 */
	protected function getMethod($method)
	{
		return $method != 'GET' ? 'POST' : $method;
	}

	/**
	 * Get the form action from the options.
	 *
	 * @param  array   $options
	 * @return string
	 */
	protected function getAction(array $options)
	{
		// We will also check for a "route" or "action" parameter on the array so that
		// developers can easily specify a route or controller action when creating
		// a form providing a convenient interface for creating the form actions.
		if (isset($options['url']))
		{
			return $this->getUrlAction($options['url']);
		}

		if (isset($options['route']))
		{
			return $this->getRouteAction($options['route']);
		}

		// If an action is available, we are attempting to open a form to a controller
		// action route. So, we will use the URL generator to get the path to these
		// actions and return them from the method. Otherwise, we'll use current.
		elseif (isset($options['action']))
		{
			return $this->getControllerAction($options['action']);
		}

		return $this->url->current();
	}

	/**
	 * Get the action for a "url" option.
	 *
	 * @param  array|string  $options
	 * @return string
	 */
	protected function getUrlAction($options)
	{
		if (is_array($options))
		{
			return $this->url->to($options[0], array_slice($options, 1));
		}

		return $this->url->to($options);
	}

	/**
	 * Get the action for a "route" option.
	 *
	 * @param  array|string  $options
	 * @return string
	 */
	protected function getRouteAction($options)
	{
		if (is_array($options))
		{
			return $this->url->route($options[0], array_slice($options, 1));
		}

		return $this->url->route($options);
	}

	/**
	 * Get the action for an "action" option.
	 *
	 * @param  array|string  $options
	 * @return string
	 */
	protected function getControllerAction($options)
	{
		if (is_array($options))
		{
			return $this->url->action($options[0], array_slice($options, 1));
		}

		return $this->url->action($options);
	}

	/**
	 * Get the form appendage for the given method.
	 *
	 * @param  string  $method
	 * @return string
	 */
	protected function getAppendage($method)
	{
		$method = strtoupper($method);

		if ($method == 'PUT' or $method == 'DELETE')
		{
			return $this->hidden('_method', $method);
		}

		return '';
	}

	/**
	 * Get the ID attribute for a field name.
	 *
	 * @param  string  $name
	 * @param  array   $attributes
	 * @return string
	 */
	protected function getIdAttribute($name, $attributes)
	{
		if (array_key_exists('id', $attributes))
		{
			return $attributes['id'];
		}

		if (in_array($name, $this->labels))
		{
			return $name;
		}
	}

	/**
	 * Get the value that should be assigned to the field.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @return string
	 */
	protected function getValueAttribute($name, $value)
	{
		if ( ! is_null($value)) return $value;

		if (isset($this->session) and $this->session->hasOldInput($name))
		{
			return $this->session->getOldInput($name);
		}

		if (isset($this->model) and isset($this->model[$name]))
		{
			return $this->model[$name];
		}
	}

	/**
	 * Get the session store implementation.
	 *
	 * @param  Illuminate\Session\Store  $session
	 * @return void
	 */
	public function getSessionStore()
	{
		return $this->session;
	}

	/**
	 * Set the session store implementation.
	 *
	 * @param  Illuminate\Session\Store  $session
	 * @return void
	 */
	public function setSessionStore(Session $session)
	{
		$this->session = $session;
	}

	/**
	 * Dynamically handle calls to the form builder.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		if (isset($this->macros[$method]))
		{
			return call_user_func_array($this->macros[$method], $parameters);
		}

		throw new \BadMethodCallException("Method {$method} does not exist.");
	}

}
