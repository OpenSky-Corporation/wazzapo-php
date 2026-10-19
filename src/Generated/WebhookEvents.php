<?php

// Создано sdk/codegen/php.py из sdk/openapi.json. Не править руками: python -m codegen php

declare(strict_types=1);

namespace Wazzapo\Generated;

/** События вебхуков: имя → [описание, схема data в описании API]. */
final class WebhookEvents
{
    public const ALL = [
        'contact.created' => ['Новый контакт: добавлен вручную, через API, пришёл с сообщением или письмом', 'WebhookContactCreated'],
        'contact.updated' => ['В карточке контакта поменялись имя, телефон, почта, компания, теги, заметки, блокировка или дополнительные поля: список changes и кто изменил (changed_by)', 'WebhookContactUpdated'],
        'conversation.assigned' => ['Диалог назначили менеджеру, передали другому или сняли назначение', 'WebhookConversationAssigned'],
        'conversation.note_added' => ['Менеджер оставил заметку в диалоге (клиент её не видит)', 'WebhookNoteAdded'],
        'conversation.status_changed' => ['Статус диалога изменился: open, pending, snoozed, resolved', 'WebhookConversationStatusChanged'],
        'deal.changed' => ['У сделки поменялись сумма, название, ответственный, клиент или поля воронки: список changes «поле, было, стало»', 'WebhookDealChanged'],
        'deal.closed' => ['Сделка выиграна или проиграна: outcome won или lost, причина проигрыша lost_reason, кто закрыл', 'WebhookDealClosed'],
        'deal.created' => ['Новая сделка: из карточки, правилом по входящему сообщению, сценарием или через API', 'WebhookDealCreated'],
        'deal.stage_changed' => ['Сделку перевели на другой этап: из карточки, перетаскиванием, массово, правилом, сценарием или после оплаты', 'WebhookDealStageChanged'],
        'invoice.status_changed' => ['Счёт Kaspi (ApiPay) сменил статус: processing, pending, paid, cancelled, expired, error, partially_refunded', 'WebhookInvoiceStatusChanged'],
        'message.incoming' => ['Клиент написал', 'WebhookMessageIncoming'],
        'message.outgoing' => ['Любое сообщение клиенту: менеджер из чата, сценарий или бот, рассылка, шаблон, отправка через API', 'WebhookMessageOutgoing'],
        'message.status' => ['Изменение статуса исходящего сообщения (отправлено, доставлено, прочитано, ошибка)', 'WebhookMessageStatus'],
        'session.connected' => ['WhatsApp-сессия успешно подключена', 'WebhookSession'],
        'session.disconnected' => ['WhatsApp-сессия отключена', 'WebhookSession'],
        'task.created' => ['Новая задача менеджеру: из сделки или чата, через API, сценарием, правилом этапа воронки или следующим шагом после выполненной задачи', 'WebhookTaskCreated'],
        'template.category_changed' => ['Meta сменила категорию шаблона (MARKETING, UTILITY, AUTHENTICATION), от неё зависит цена', 'WebhookTemplateEvent'],
        'template.quality_changed' => ['Meta изменила оценку качества шаблона: GREEN, YELLOW, RED', 'WebhookTemplateEvent'],
        'template.status_updated' => ['Meta проверила шаблон: APPROVED, REJECTED (с причиной reason), PAUSED, DISABLED', 'WebhookTemplateEvent'],
        'test' => ['Тестовое событие', 'WebhookTestEvent'],
    ];
}
