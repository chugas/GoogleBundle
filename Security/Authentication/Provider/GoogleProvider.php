<?php

/*
 * This file is part of the BITGoogleBundle package.
 *
 * (c) bitgandtter <http://bitgandtter.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace BIT\GoogleBundle\Security\Authentication\Provider;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use BIT\GoogleBundle\Security\User\UserManagerInterface;
use BIT\GoogleBundle\Security\Authentication\Token\GoogleUserToken;
use BIT\GoogleBundle\Google\GoogleSessionPersistence;

class GoogleProvider implements AuthenticationProviderInterface
{
  protected $googleApi;
  protected $providerKey;
  protected $userProvider;
  protected $userChecker;
  protected $createIfNotExists;
  
  public function __construct( $providerKey, GoogleSessionPersistence $googleApi,
      UserProviderInterface $userProvider = null, UserCheckerInterface $userChecker = null, $createIfNotExists = false )
  {
    $errorMessage = '$userChecker cannot be null, if $userProvider is not null.';
    if ( null !== $userProvider && null === $userChecker )
      throw new \InvalidArgumentException( $errorMessage);
    
    $errorMessage = 'The $userProvider must implement UserManagerInterface if $createIfNotExists is true.';
    if ( $createIfNotExists && !$userProvider instanceof UserManagerInterface )
      throw new \InvalidArgumentException( $errorMessage);
    
    $this->providerKey = $providerKey;
    $this->googleApi = $googleApi;
    $this->userProvider = $userProvider;
    $this->userChecker = $userChecker;
    $this->createIfNotExists = $createIfNotExists;
  }
  
  public function authenticate( TokenInterface $token )
  {
    if ( !$this->supports( $token ) )
      return null;
    try
    {
      $this->googleApi->authenticate( );
      $this->googleApi->setAccessToken( $this->googleApi->getAccessToken( ) );
      
      $user = $token->getUser( );
      
      if ( $user instanceof UserInterface )
      {
        $this->userChecker->checkPostAuth( $user );
        
        $newToken = new GoogleUserToken( $this->providerKey, $user, $user->getRoles( ));
        $newToken->setAttributes( $token->getAttributes( ) );
        
        return $newToken;
      }
      
      $userData = $this->googleApi->getOAuth( )->userinfo->get( );
      if ( $uid = $userData[ "id" ] )
      {
        $this->googleApi->setPersistentData( 'access_token', $this->googleApi->getAccessToken( ) );
        $this->googleApi->setPersistentData( 'user_id', $uid );
        
        $newToken = $this->createAuthenticatedToken( $uid );
        $newToken->setAttributes( $token->getAttributes( ) );
        
        return $newToken;
      }
      
      throw new AuthenticationException( 'The Google user could not be retrieved from the session.');
    }
    catch ( AuthenticationException $failed )
    {
      throw $failed;
    }
    catch ( \Exception $failed )
    {
      throw new AuthenticationException( $failed->getMessage( ), ( int ) $failed->getCode( ), $failed);
    }
  }
  
  public function supports( TokenInterface $token )
  {
    return $token instanceof GoogleUserToken && $this->providerKey === $token->getProviderKey( );
  }
  
  protected function createAuthenticatedToken( $uid )
  {
    if ( null === $this->userProvider )
      return new GoogleUserToken( $this->providerKey, $uid);
    
    try
    {
      $user = $this->userProvider->loadUserByUsername( $uid );
      $this->userChecker->checkPostAuth( $user );
    }
    catch ( UsernameNotFoundException $ex )
    {
      if ( !$this->createIfNotExists )
        throw $ex;
      
      $user = $this->userProvider->createUserFromUid( $uid );
    }
    
    if ( !$user instanceof UserInterface )
      throw new \RuntimeException( 'User provider did not return an implementation of user interface.');
    
    return new GoogleUserToken( $this->providerKey, $user, $user->getRoles( ));
  }
}
