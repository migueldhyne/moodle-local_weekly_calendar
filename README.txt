# local\_advanced\_calendar

This plugin provides a weekly calendar view and allows you to export each week as a PDF document.

## Features

* Monthly and weekly calendar views
* Export and print in PDF format
* Option to customize colors and filters

## Requirements

* Moodle **4.2** or higher
* PHP **8.0** or higher
* **Dompdf** (via the local\_dompdf plugin) for PDF rendering:
  [https://moodle.org/plugins/local\_dompdf](https://moodle.org/plugins/local_dompdf)

## Installation

1. Unzip the archive and place the `advanced_calendar` folder into `local/advanced_calendar` of your Moodle instance.
2. Log in as an administrator and trigger the database upgrade (Administration → Notifications).
3. Check the settings in Administration → Plugins → Local plugins → Advanced Calendar.

## Usage

* **Monthly view** (default):
  `https://<your-moodle>/calendar/view.php`

* **Weekly view**:
  `https://<your-moodle>/local/advanced_calendar/view.php?view=week`

## ADVANCED

In order to obtain a correct print rendering in PDF format, this plugin requires the installation of the Dompdf library ([https://moodle.org/plugins/local\_dompdf](https://moodle.org/plugins/local_dompdf)).

To access the calendar in weekly mode, use the following URL:

[https://<your-moodle>/local/advanced\_calendar/view.php?view=week](https://XXX/local/advanced_calendar/view.php?view=week)

Enjoy!

## Contribute

Contributions are welcome!
– Open an issue or pull request on the Git repository.
– Make sure to follow Moodle coding standards.

## License

This plugin is distributed under the **GPL v3** license.