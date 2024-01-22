<?php

declare(strict_types=1);

namespace App\Data;

use SignNow\Api\Entity\Document\Field\AbstractField;
use SignNow\Api\Service\EntityManager\EntityManager;

class EmbeddedInvite
{
    private EntityManager $authentication;
    
    private string $uploadDocumentPath = '';

    private array $fields = [];

    private string $signerEmail = '';

    private string $signerRoleName = '';
    
    private string $signerRoleId = '';

    private string $documentId = '';
    
    private array $invites = [];
    
    private string $signingLink = '';

    private string $signerFirstName = '';

    private string $signerLastName = '';

    private string $redirectUrl = '';

    public function getAuthentication(): EntityManager
    {
        return $this->authentication;
    }

    public function setAuthentication(EntityManager $authentication): EmbeddedInvite
    {
        $this->authentication = $authentication;
        
        return $this;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function addField(AbstractField $field): EmbeddedInvite
    {
        $this->fields[] = $field;

        return $this;
    }

    public function getSignerEmail(): string
    {
        return $this->signerEmail;
    }

    public function setSignerEmail(string $signerEmail): EmbeddedInvite
    {
        $this->signerEmail = $signerEmail;

        return $this;
    }

    public function getSignerRoleName(): string
    {
        return $this->signerRoleName;
    }

    public function setSignerRoleName(string $signerRoleName): EmbeddedInvite
    {
        $this->signerRoleName = $signerRoleName;

        return $this;
    }

    public function getSignerRoleId(): string
    {
        return $this->signerRoleId;
    }

    public function setSignerRoleId(string $signerRoleId): EmbeddedInvite
    {
        $this->signerRoleId = $signerRoleId;

        return $this;
    }

    public function getSignerFirstName(): string
    {
        return $this->signerFirstName;
    }

    public function setSignerFirstName(string $signerFirstName): EmbeddedInvite
    {
        $this->signerFirstName = $signerFirstName;

        return $this;
    }

    public function getSignerLastName(): string
    {
        return $this->signerLastName;
    }

    public function setSignerLastName(string $signerLastName): EmbeddedInvite
    {
        $this->signerLastName = $signerLastName;

        return $this;
    }

    public function getDocumentId(): string
    {
        return $this->documentId;
    }

    public function setDocumentId(string $documentId): EmbeddedInvite
    {
        $this->documentId = $documentId;

        return $this;
    }

    public function getUploadDocumentPath(): string
    {
        return $this->uploadDocumentPath;
    }

    public function setUploadDocumentPath(string $path): EmbeddedInvite
    {
        $this->uploadDocumentPath = $path;

        return $this;
    }

    public function getInvites(): array
    {
        return $this->invites;
    }

    public function setInvites(array $invites): EmbeddedInvite
    {
        $this->invites = $invites;

        return $this;
    }

    public function getSigningLink(): string
    {
        return $this->signingLink;
    }

    public function setSigningLink(string $signingLink): EmbeddedInvite
    {
        $this->signingLink = $signingLink;

        return $this;
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    public function setRedirectUrl(string $redirectUrl): EmbeddedInvite
    {
        $this->redirectUrl = $redirectUrl;
        
        return $this;
    }
}
