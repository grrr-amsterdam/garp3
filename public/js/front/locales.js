
// LOCALES:
Garp.locale = (typeof Garp.locale == 'undefined') ? {} : Garp.locale;
Garp.apply(Garp.locale, {

	// Garp.relativeDate
	'years': 'jaren',
	'year': 'jaar',
	'months': 'maanden',
	'month': 'maand',
	'weeks': 'weken',
	'week': 'week',
	'days': 'dagen',
	'day': 'dag',
	'hours': 'uur',
	'hour': 'uur',
	'minutes': 'minuten',
	'minute': 'minuut',
	'ago' : 'geleden',
	'less than a minute': 'minder dan een minuut',

	//Garp.FormHelper.Duplicator
	'Add': 'Toevoegen',
	'Remove': 'Verwijderen',

	//Garp.FormHelper.Validator
	// IMPORTANT: Keep synced with /garp/application/data/i18n/nl.php
	"'${1}' is not valid" : "'${1}' is niet geldig",
	"Value is required and can't be empty" : "Dit veld is verplicht",
	"'${1}' does not appear to be a postal code" : "'${1}' is geen geldige postcode",
	"'${1}' does not match against pattern '${2}'" : "Het ingevulde '${1}' komt niet overeen met het patroon voor veld '${2}",
	"'${1}' is not a valid email address in the basic format local-part@hostname" : "'${1}' is geen geldig e-mailadres in het formaat account@voorbeeld.nl",
	"'${1}' is not a valid Dutch postcode" : "'${1}' is geen geldige Nederlandse postcode",
	"'${1}' is not a valid URL" : "'${1}' is geen geldige URL",
	"Value doesn't match": "Waarde komt niet overeen",
	
	//qq.FileUploader
	"{file} has invalid extension. Only {extensions} are allowed.": "{file} heeft niet de juiste extensie. De volgende extensie(s) zijn toegestaan: {extensions}",
	"{file} is too large, maximum file size is {sizeLimit}.": "{file} is te groot. Maximum grootte is {sizeLimit}",
	"{file} is too small, minimum file size is {minSizeLimit}.": "{file} is te klein. Minimale grootte is {minSizeLimit}",
	"{file} is empty, please select files again without it.": "{file} is leeg.",
	"The files are being uploaded, if you leave now the upload will be cancelled.": "Bestanden worden geüpload. Het uploaden wordt onderbroken als u weggaat.",
	"Something went wrong while uploading. Please try again later" : "Er ging iets mis met uploaden. Probeer 't later opnieuw"
});