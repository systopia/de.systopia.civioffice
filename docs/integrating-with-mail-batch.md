# Sending Emails With Personalized Attachments

You can integrate CiviOffice with
the [de.systopia.mailbatch](https://github.com/systopia/de.systopia.mailbatch)
extension. This allows you to send out personalized email attachments to your
contacts. The minimum version required for this is MailBatch 1.2-alpha1 (release
upcoming, feature is in master branch as of writing).

If both CiviOffice and MailBatch are installed in sufficient versions, the
integration will work without further configuration.

To use the feature, search for contacts and select the search action 'Send
Emails (via MailBatch)'. In the options interface, under 'Attachments', select '
CiviOffice Document' and choose the template you want to use for your
attachments. Any valid tokens will be replaced in the usual way.
