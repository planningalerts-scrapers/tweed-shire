require "icon_scraper"

IconScraper.scrape_with_params(
  url: "https://s1.tweed.nsw.gov.au/Pages/XC.Track",
  period: "thismonth",
  types: ["DA", "CDC"]
) do |record|
  record["address"] = record["address"].gsub(",", "")
  record["info_url"] = record["info_url"].split("?")[0] + "/Pages/XC.Track/SearchApplication.aspx?" + record["info_url"].split("?")[1]
  IconScraper.save(record)
end
