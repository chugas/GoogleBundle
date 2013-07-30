<?php

/*
 * This file is part of the BITGoogleBundle package.
 *
 * (c) bitgandtter <http://bitgandtter.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace BIT\GoogleBundle\DependencyInjection;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
  /**
   * Generates the configuration tree.
   *
   * @return TreeBuilder
   */
  
  public function getConfigTreeBuilder( )
  {
    $treeBuilder = new TreeBuilder( );
    $rootNode = $treeBuilder->root( 'bit_google' );
    
    $rootNode->fixXmlConfig( 'permission', 'permissions' )->children( )// childrens
        ->scalarNode( 'app_name' )->isRequired( )->cannotBeEmpty( )->end( ) // app name
        ->scalarNode( 'client_id' )->isRequired( )->cannotBeEmpty( )->end( ) // client id
        ->scalarNode( 'client_secret' )->cannotBeEmpty( )->end( ) // client secret
        ->scalarNode( 'callback_route' )->cannotBeEmpty( )->end( ) // redirect callback
        ->scalarNode( 'callback_url' )->isRequired( )->cannotBeEmpty( )->end( ) // redirect callback
        ->arrayNode( 'scopes' )->addDefaultsIfNotSet( )->children( ) // scopes by api
        ->arrayNode( 'openid' )->prototype( 'scalar' )->isRequired( )->end( )->end( ) // openid
        ->booleanNode( 'contact' )->defaultValue( True )->end( )->end( ) // contact
        ->end( ) // end scopes by api
        ->scalarNode( 'state' )->defaultValue( 'auth' )->end( ) // default state auth
        ->scalarNode( 'access_type' )->defaultValue( 'online' )->end( ) // default acess type online
        ->scalarNode( 'approval_prompt' )->defaultValue( 'auto' )->end( ) // 
        ->arrayNode( 'class' )->addDefaultsIfNotSet( )->children( ) // clasess
        ->scalarNode( 'api' )->defaultValue( 'BIT\GoogleBundle\Google\GoogleSessionPersistence' )->end( ) // api
        ->scalarNode( 'contact' )->defaultValue( 'BIT\GoogleBundle\Google\GoogleContact' )->end( ) // contact
        ->scalarNode( 'helper' )->defaultValue( 'BIT\GoogleBundle\Templating\Helper\GoogleHelper' )->end( ) // template helper
        ->scalarNode( 'twig' )->defaultValue( 'BIT\GoogleBundle\Twig\Extension\GoogleExtension' )->end( ) // twig ext
        ->end( ) // end clasess
        ->end( )->end( );
    
    return $treeBuilder;
  }
}
