<?php

/**
 * @file
 * Contains og_invite.page.inc.
 *
 * Page callback for Invite entities.
 */

/**
 * Prepares variables for Invite templates.
 *
 * Default template: og_invite.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_og_invite(array &$variables) {
  // Fetch OgInvite Entity Object.
  // $og_invite = $variables['elements']['#og_invite'];.
  // Helpful $content variable for templates.
  // foreach (Element::children($variables['elements']) as $key) {
  // $variables['content'][$key] = $variables['elements'][$key];
  // }.
}
