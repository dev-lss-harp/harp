<?php 
namespace Harp\lib\HarpOauth2;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Grant\ImplicitGrant;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;

class Oauth2Server
{
    public $Server;
    private $clientRepository;
    private $accessTokenRepository;
    private $scopeRepository;

    public function __construct
    (
        ClientRepositoryInterface $clientRepository,
        AccessTokenRepositoryInterface $accessTokenRepository,
        ScopeRepositoryInterface $scopeRepository,
        $privateKey,
        $encryptionKey
    )
    {
        $this->clientRepository = $clientRepository;
        $this->accessTokenRepository = $accessTokenRepository;
        $this->scopeRepository = $scopeRepository;

        $this->Server = new AuthorizationServer(
            $this->clientRepository,
            $this->accessTokenRepository,
            $this->scopeRepository,
            $privateKey,
            $encryptionKey
        );
    }

    public function enableClientCredentialsGrant($expired = 'PT1H')
    {
        $this->Server->enableGrantType(
            new \League\OAuth2\Server\Grant\ClientCredentialsGrant(),
            new \DateInterval($expired) // access tokens will expire after 1 hour
        );
    }

    public function enablePasswordGrant
    (
        UserRepositoryInterface $UserRepository,
        RefreshTokenRepositoryInterface $RefresTokenRepository,
        $expired = 'PT1H',
        $refreshExpired = 'P1M'
    )
    {
        $grant = new \League\OAuth2\Server\Grant\PasswordGrant(
            $UserRepository,
            $RefresTokenRepository
       );
       
       $grant->setRefreshTokenTTL(new \DateInterval($refreshExpired)); // refresh tokens will expire after 1 month
       
       // Enable the password grant on the server
       $this->Server->enableGrantType(
           $grant,
           new \DateInterval($expired) // access tokens will expire after 1 hour
       );
    }

    public function enableAuthCodeGrant(AuthCodeRepositoryInterface $authCodeRepository, RefreshTokenRepositoryInterface $refreshTokenRepository)
    {
        $grant = new \League\OAuth2\Server\Grant\AuthCodeGrant(
            $authCodeRepository,
            $refreshTokenRepository,
            new \DateInterval('PT10M') 
        );
       
       $grant->setRefreshTokenTTL(new \DateInterval('P1M')); 
       
       $this->Server->enableGrantType(
           $grant,
           new \DateInterval('PT1H') 
           
       );
    }

    public function enableImplicitGrant()
    {
        $this->Server->enableGrantType(
            new ImplicitGrant(new \DateInterval('PT1H')),
            new \DateInterval('PT1H') // access tokens will expire after 1 hour
        );
    }

    public function enableRefreshToken(RefreshTokenRepositoryInterface $refreshTokenRepository)
    {
        $grant = new \League\OAuth2\Server\Grant\RefreshTokenGrant($refreshTokenRepository);
        $grant->setRefreshTokenTTL(new \DateInterval('P1M'));

        $this->Server->enableGrantType(
            $grant,
            new \DateInterval('PT1H')
        );
    }
}