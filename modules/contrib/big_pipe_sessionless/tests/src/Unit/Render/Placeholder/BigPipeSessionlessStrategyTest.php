<?php

namespace Drupal\Tests\big_pipe_sessionless\Unit\Render\Placeholder;

use Drupal\big_pipe\Render\Placeholder\BigPipeStrategy;
use Drupal\big_pipe\Tests\BigPipePlaceholderTestCases;
use Drupal\big_pipe_sessionless\Render\Placeholder\BigPipeSessionlessStrategy;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\SessionConfigurationInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\big_pipe_sessionless\Render\Placeholder\BigPipeSessionlessStrategy
 * @group big_pipe_sessionless
 *
 * @see \Drupal\Tests\big_pipe\Unit\Render\Placeholder\BigPipeStrategyTest
 */
class BigPipeSessionlessStrategyTest extends UnitTestCase {

  /**
   * @covers ::processPlaceholders
   *
   * @dataProvider placeholdersProvider
   */
  public function testProcessPlaceholders(array $placeholders, $method, $route_match_has_no_big_pipe_option, $request_has_session, $request_has_big_pipe_nojs_cookie, array $expected_big_pipe_placeholders) {
    $request = new Request();
    $request->setMethod($method);
    if ($request_has_big_pipe_nojs_cookie) {
      $request->cookies->set(BigPipeSessionlessStrategy::NOJS_COOKIE, 1);
    }
    $request_stack = $this->prophesize(RequestStack::class);
    $request_stack->getCurrentRequest()
      ->willReturn($request);

    $session_configuration = $this->prophesize(SessionConfigurationInterface::class);
    $session_configuration->hasSession(Argument::type(Request::class))
      ->willReturn($request_has_session);

    $route = $this->prophesize(Route::class);
    $route->getOption('_no_big_pipe')
      ->willReturn($route_match_has_no_big_pipe_option);
    $route_match = $this->prophesize(RouteMatchInterface::class);
    $route_match->getRouteObject()
      ->willReturn($route);

    $big_pipe_strategy = new BigPipeSessionlessStrategy($session_configuration->reveal(), $request_stack->reveal(), $route_match->reveal());
    $processed_placeholders = $big_pipe_strategy->processPlaceholders($placeholders);

    if ($request->isMethodCacheable() && !$route_match_has_no_big_pipe_option && !$request_has_session) {
      $this->assertSameSize($expected_big_pipe_placeholders, $processed_placeholders, 'BigPipe is able to deliver all placeholders.');
      foreach (array_keys($placeholders) as $placeholder) {
        $this->assertSame($expected_big_pipe_placeholders[$placeholder], $processed_placeholders[$placeholder], "Verifying how BigPipeStrategy handles the placeholder '$placeholder'");
      }
    }
    else {
      $this->assertSame(0, count($processed_placeholders));
    }
  }

  /**
   * @see \Drupal\big_pipe\Tests\BigPipePlaceholderTestCases
   */
  public function placeholdersProvider() {
    $cases = BigPipePlaceholderTestCases::cases();

    // Generate $placeholders variable as expected by
    // \Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface::processPlaceholders().
    $placeholders = [
      $cases['html']->placeholder                             => $cases['html']->placeholderRenderArray,
      $cases['html_attribute_value']->placeholder             => $cases['html_attribute_value']->placeholderRenderArray,
      $cases['html_attribute_value_subset']->placeholder      => $cases['html_attribute_value_subset']->placeholderRenderArray,
      $cases['edge_case__invalid_html']->placeholder          => $cases['edge_case__invalid_html']->placeholderRenderArray,
      $cases['edge_case__html_non_lazy_builder']->placeholder => $cases['edge_case__html_non_lazy_builder']->placeholderRenderArray,
      $cases['exception__lazy_builder']->placeholder          => $cases['exception__lazy_builder']->placeholderRenderArray,
      $cases['exception__embedded_response']->placeholder     => $cases['exception__embedded_response']->placeholderRenderArray,
    ];

    // In the 'no session' cases, BigPipe *always* use no-JS placeholders, so
    // the 'cookies:big_pipe_nojs' cache context is unnecessary.
    $remove_no_js_cookie_cache_context = function (array $render_array) {
      $render_array['#cache']['contexts'] = array_diff($render_array['#cache']['contexts'], ['cookies:big_pipe_nojs']);
      return $render_array;
    };

    return [
      '_no_big_pipe absent, no session, no-JS cookie absent: no-JS BigPipe placeholder used for HTML placeholders' => [$placeholders, 'GET', FALSE, FALSE, FALSE, [
        $cases['html']->placeholder                             => $remove_no_js_cookie_cache_context($cases['html']->bigPipeNoJsPlaceholderRenderArray),
        $cases['html_attribute_value']->placeholder             => $remove_no_js_cookie_cache_context($cases['html_attribute_value']->bigPipeNoJsPlaceholderRenderArray),
        $cases['html_attribute_value_subset']->placeholder      => $remove_no_js_cookie_cache_context($cases['html_attribute_value_subset']->bigPipeNoJsPlaceholderRenderArray),
        $cases['edge_case__invalid_html']->placeholder          => $remove_no_js_cookie_cache_context($cases['edge_case__invalid_html']->bigPipeNoJsPlaceholderRenderArray),
        $cases['edge_case__html_non_lazy_builder']->placeholder => $remove_no_js_cookie_cache_context($cases['edge_case__html_non_lazy_builder']->bigPipeNoJsPlaceholderRenderArray),
        $cases['exception__lazy_builder']->placeholder          => $remove_no_js_cookie_cache_context($cases['exception__lazy_builder']->bigPipeNoJsPlaceholderRenderArray),
        $cases['exception__embedded_response']->placeholder     => $remove_no_js_cookie_cache_context($cases['exception__embedded_response']->bigPipeNoJsPlaceholderRenderArray),
      ]],
      '_no_big_pipe absent, no session, no-JS cookie present: no-JS BigPipe placeholder used for HTML placeholders' => [$placeholders, 'GET', FALSE, FALSE, TRUE, [
        $cases['html']->placeholder                             => $remove_no_js_cookie_cache_context($cases['html']->bigPipeNoJsPlaceholderRenderArray),
        $cases['html_attribute_value']->placeholder             => $remove_no_js_cookie_cache_context($cases['html_attribute_value']->bigPipeNoJsPlaceholderRenderArray),
        $cases['html_attribute_value_subset']->placeholder      => $remove_no_js_cookie_cache_context($cases['html_attribute_value_subset']->bigPipeNoJsPlaceholderRenderArray),
        $cases['edge_case__invalid_html']->placeholder          => $remove_no_js_cookie_cache_context($cases['edge_case__invalid_html']->bigPipeNoJsPlaceholderRenderArray),
        $cases['edge_case__html_non_lazy_builder']->placeholder => $remove_no_js_cookie_cache_context($cases['edge_case__html_non_lazy_builder']->bigPipeNoJsPlaceholderRenderArray),
        $cases['exception__lazy_builder']->placeholder          => $remove_no_js_cookie_cache_context($cases['exception__lazy_builder']->bigPipeNoJsPlaceholderRenderArray),
        $cases['exception__embedded_response']->placeholder     => $remove_no_js_cookie_cache_context($cases['exception__embedded_response']->bigPipeNoJsPlaceholderRenderArray),
      ]],
      '_no_big_pipe present, no session, no-JS cookie absent' => [$placeholders, 'GET', TRUE, FALSE, FALSE, []],
      '_no_big_pipe present, no session, no-JS cookie present' => [$placeholders, 'GET', TRUE, FALSE, TRUE, []],
      '_no_big_pipe present, session, no-JS cookie absent' => [$placeholders, 'GET', TRUE, TRUE, FALSE, []],
      '_no_big_pipe present, session, no-JS cookie present' => [$placeholders, 'GET', TRUE, TRUE, TRUE, []],
      '_no_big_pipe absent, session, no-JS cookie absent: (JS-powered) BigPipe placeholder used for HTML placeholders' => [$placeholders, 'GET', FALSE, TRUE, FALSE, []],
      '_no_big_pipe absent, session, no-JS cookie absent: (JS-powered) BigPipe placeholder used for HTML placeholders — but unsafe method' => [$placeholders, 'POST', FALSE, TRUE, FALSE, []],
      '_no_big_pipe absent, session, no-JS cookie present: no-JS BigPipe placeholder used for HTML placeholders' => [$placeholders, 'GET', FALSE, TRUE, TRUE, []],
      '_no_big_pipe absent, session, no-JS cookie present: no-JS BigPipe placeholder used for HTML placeholders — but unsafe method' => [$placeholders, 'POST', FALSE, TRUE, TRUE, []],
    ];
  }

}
