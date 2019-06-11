require "icon_scraper"

IconScraper.scrape_with_params(
  url: "https://s1.tweed.nsw.gov.au/Pages/XC.Track",
  period: "thismonth",
  types: ["DA", "CDC"]
) do |record|
  IconScraper.save(record)
end
