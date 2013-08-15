<?php

/*
 * This file is part of the BITGoogleBundle package.
 *
 * (c) bitgandtter <http://bitgandtter.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace BIT\GoogleBundle\Google;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Session\Session;
use Google_HttpRequest;
use SimpleXMLElement;

/**
 * Implements Google Contact.
 *
 */
class GoogleContact
{
  private $api;
  
  public function __construct( GoogleSessionPersistence $api, $request )
  {
    $this->api = $api;
    $this->api->authenticate($request->get('code'));
  }
  
  private function parse( $string )
  {
    $array = $this->addNode( simplexml_load_string( $string ) );
    $contacts = array( );
    foreach ( $array[ "children" ] as $item )
    {
      foreach ( $item as $key => $element )
      {
        if ( $element == "entry" )
        {
          $contact = array( );
          foreach ( $item[ "children" ] as $key => $data )
          {
            switch ( $data[ 'name' ] )
            {
              case "title":
                {
                  if ( array_key_exists( 'content', $data ) )
                    $contact[ "name" ] = $data[ 'content' ];
                  break;
                }
              case "email":
                {
                  if ( array_key_exists( 'attributes', $data ) && array_key_exists( 'address', $data[ 'attributes' ] ) )
                    $contact[ "email" ] = $data[ 'attributes' ][ 'address' ];
                  break;
                }
            }
          }
          
          if ( array_key_exists( 'email', $contact ) )
            $contacts[ $contact[ 'email' ] ] = $contact;
        }
      }
    }
    return $contacts;
  }
  
  private function addNode( $node, &$parent = null, $namespace = '', $recursive = false )
  {
    $namespaces = $node->getNameSpaces( true );
    $content = "$node";
    
    $r[ 'name' ] = $node->getName( );
    if ( !$recursive )
    {
      $tmp = array_keys( $node->getNameSpaces( false ) );
      $r[ 'namespace' ] = $tmp[ 0 ];
      $r[ 'namespaces' ] = $namespaces;
    }
    
    if ( $namespace )
      $r[ 'namespace' ] = $namespace;
    
    if ( $content )
      $r[ 'content' ] = $content;
    
    foreach ( $namespaces as $pre => $ns )
    {
      foreach ( $node->children( $ns ) as $k => $v )
        $this->addNode( $v, $r[ 'children' ], $pre, true );
      
      foreach ( $node->attributes( $ns ) as $k => $v )
        $r[ 'attributes' ][ $k ] = "$pre:$v";
    }
    
    foreach ( $node->children( ) as $k => $v )
      $this->addNode( $v, $r[ 'children' ], '', true );
    
    foreach ( $node->attributes( ) as $k => $v )
      $r[ 'attributes' ][ $k ] = "$v";
    
    $parent[ ] = &$r;
    return $parent[ 0 ];
  }
  
  public function getContacts( $maxResult = 9999 )
  {
    $url = "https://www.google.com/m8/feeds/contacts/default/full?max-results=" . $maxResult;
    $val = $this->api->getIo( )->authenticatedRequest( new Google_HttpRequest( $url) );
    return $this->parse( $val->getResponseBody( ) );
  }
}
