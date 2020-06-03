Feature: Restrict loading of components and routes
  In order to secure specific pages in my application
  As an API user
  I can secure routes and components located within those routes

  Background:
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"

  Scenario: A route retrieved by id is restricted based on the security policy
    Given there is a Route "/user-area/my-page" with a page
    When I send a "GET" request to the resource "route"
    Then the response status code should be 401

  Scenario: A route retrieved by path is restricted based on the security policy
    Given there is a Route "/user-area/my-page" with a page
    When I send a "GET" request to "/_/routes//user-area/my-page"
    Then the response status code should be 401

  Scenario: A collection of routes will not include pages what a user has no access to
    Given there is a Route "/user-area/my-page" with a page
    When I send a "GET" request to "/_/routes"
    Then the response status code should be 200
    And the JSON node "hydra:member[0]" should not exist

  @loginSuperAdmin
  Scenario: I can get a collection of routes as a super admin
    Given there is a Route "/user-area/my-page" with a page
    When I send a "GET" request to "/_/routes"
    Then the response status code should be 200
    And the JSON node "hydra:member[0]" should exist

  Scenario: A component in a restricted route cannot be loaded by an anonymous user
    Given there is a component in a route with the path "/user-area/my-page"
    When I send a "GET" request to the resource "component_0"
    Then the response status code should be 401

  @loginUser
  Scenario: A component in a restricted route can be loaded by an authorised user
    Given there is a component in a route with the path "/user-area/my-page"
    When I send a "GET" request to the resource "component_0"
    Then the response status code should be 200

  Scenario: A component within a restricted route and a public route can be loaded by an anonymous user
    Given there is a component in a route with the path "/user-area/my-page"
    And the resource "component_0" is in a route with the path "/any-page"
    When I send a "GET" request to the resource "component_0"
    Then the response status code should be 200
