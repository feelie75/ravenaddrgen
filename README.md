# ravenaddrgen
Generate random ravencoin addresses in search of a vanity address.

Generates random Ravencoin addresses. Outputs to a bunch of .csv files which you can GREP thru later to look for vanity addresses. Takes up a WHOLE lot of drive space.
If you have an SSD, generate there, grepping will be much faster.

You may need to alter paths to fit your host/server/PC.

DO NOT share your private key (the WIF) with anyone you don't trust.

You can concatenate the results into a file that contains JUST the address, so grepping will be MUCH faster:
cat *.csv | cut -d"," -f4 > allrvnaddresses.txt

Then grep that:
grep --color=always -i -E "(cocacola|ravenland|whiskers|whiskerz|meowmixx|RavenRocks)" allrvnaddresses.txt

You can add many more addresses to the search without affecting the search time. 

To use an address: with RavenCore Wallet, go to Help -> Debug -> Console and importprivkey("theWif","label",false); (you'll have to do a walletpassphrase [privatekey] 3600 first.

I think you might need to importpub as well...

*THIS IS A WORK IN PROGRESS* YMMV!


