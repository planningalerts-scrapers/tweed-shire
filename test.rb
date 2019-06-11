require 'scraperwiki'
require 'yaml'

File.delete("./data.sqlite") if File.exist?("./data.sqlite")

system("php scraper.php")

results_php = ScraperWiki.select("* from data order by council_reference")

File.open("results_php.yml", "w") do |f|
  f.write(results_php.to_yaml)
end

ScraperWiki.close_sqlite

File.delete("./data.sqlite") if File.exist?("./data.sqlite")

system("bundle exec ruby scraper.rb")

results_ruby = ScraperWiki.select("* from data order by council_reference")

File.open("results_ruby.yml", "w") do |f|
  f.write(results_ruby.to_yaml)
end

if results_ruby == results_php
  puts "Succeeded"
else
  system("diff results_php.yml results_ruby.yml")
  raise "Failed"
end
