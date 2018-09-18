# USAePay Sales Rate Monitor/Alert
Monitors the rate of sales in USAePay, sends email alert if rate drops.

To get up and running:

1. `cp config.php.template config.php`
2. Fill-in `config.php` settings.  Info about getting USAePay API credentials here: https://wiki.usaepay.com/developer/soap.
3. Run `php run.php`.
4. Check "Now (adjusted server time)" in the output, this should match your current time in USAePay.  If not, adjust `timediff` in `config.php`.
5. I would recommend running this as a cron job, every 10-30 minutes.

---

Given an `interval` (in config.php) of time (60 minute default), you USAePay account will compare the most recent interval of that time against the previous interval.

If the growth rate is below the given `threshold` (in config.php), an email is sent containing the same output of the CLI script.

Growth rate of 1 means same number of orders in both intervals;  .5 would mean half as many orders in most recent interval as previous interval.
