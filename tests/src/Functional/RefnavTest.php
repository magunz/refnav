<?php

namespace Drupal\Tests\refnav\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * A test for refnav tokens.
 *
 * @group refnav
 */
class RefnavTest extends BrowserTestBase {

  /**
   * Ignore schema errors.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'refnav',
    'refnav_test',
    'ctools',
    'token',
    'pathauto',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $article1 = $this->createNode(['title' => 'Article 1', 'type' => 'article']);
    $article2 = $this->createNode(['title' => 'Article 2', 'type' => 'article']);

    // Add a page and reference the articles.
    $node = $this->createNode(['title' => 'Test page', 'type' => 'page']);
    $node->field_articles[] = $article1->id();
    $node->field_articles[] = $article2->id();

    $node->save();
  }

  /**
   * Tests page node path.
   */
  public function testBasicPage() {
    $this->drupalGet('/page/test-page');
    $this->assertSession()->statusCodeEquals(200);

  }

  /**
   * Tests article path.
   */
  public function testArticle() {
    $this->drupalGet('/page/test-page/article-1');
    $this->assertSession()->statusCodeEquals(200);
  }

}
