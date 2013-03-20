<?php
class TextareaTest extends FormerTests
{
  // Matchers ------------------------------------------------------ /

  public function matchTextarea()
  {
    return array(
      'tag' => 'textarea',
      'content' => 'bar',
      'attributes' => array(
        'class'    => 'foo',
        'cols'     => '50',
        'data-foo' => 'bar',
        'id'       => 'foo',
        'name'     => 'foo',
        'rows'     => '10',
      ),
    );
  }

  // Tests --------------------------------------------------------- /

  public function testCanCreateTextareas()
  {
    $attributes = $this->matchTextarea();
    $textarea = $this->former->textarea('foo')->setAttributes($attributes['attributes'])->value('bar')->__toString();
    $matcher  = $this->matchTextarea();

    $this->assertControlGroup($textarea);
    $this->assertHTML($matcher, $textarea);
  }
}
