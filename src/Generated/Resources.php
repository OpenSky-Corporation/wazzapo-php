<?php

// Создано sdk/codegen/php.py из sdk/openapi.json. Не править руками: python -m codegen php

declare(strict_types=1);

namespace Wazzapo\Generated;

use Wazzapo\HttpClient;

/** Ресурсы API как свойства клиента: $client->messages, $client->phoneNumbers. */
trait Resources
{
    public readonly Contacts $contacts;
    public readonly Conversations $conversations;
    public readonly Deals $deals;
    public readonly Media $media;
    public readonly Messages $messages;
    public readonly PhoneNumbers $phoneNumbers;
    public readonly Pipelines $pipelines;
    public readonly Tasks $tasks;
    public readonly Team $team;
    public readonly Templates $templates;
    public readonly WebhookEventTypes $webhookEventTypes;
    public readonly Webhooks $webhooks;

    private function initResources(HttpClient $http): void
    {
        $this->contacts = new Contacts($http);
        $this->conversations = new Conversations($http);
        $this->deals = new Deals($http);
        $this->media = new Media($http);
        $this->messages = new Messages($http);
        $this->phoneNumbers = new PhoneNumbers($http);
        $this->pipelines = new Pipelines($http);
        $this->tasks = new Tasks($http);
        $this->team = new Team($http);
        $this->templates = new Templates($http);
        $this->webhookEventTypes = new WebhookEventTypes($http);
        $this->webhooks = new Webhooks($http);
    }
}
