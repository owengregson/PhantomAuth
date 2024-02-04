<h1 id="phantomauth">PhantomAuth</h1>
<p>A PHP Authentication System with modular resource storage, rate-limiting, ip-limiting, keys, and configuration.</p>
<p>I&#39;ve been using this authentication system for a long time in my projects but it had some major flaws and security issues that I needed to fix. After fixing everything, I&#39;ve decided to release the auth here for others to use.
PhantomAuth integrates with my other project, CMAnalytics, for powerful discord logging (if you enable that feature.)</p>
<p>⚠️ <strong>I am not liable for any misuse of this authentication system such as using it to &#39;grab IPs&#39; or collect other user data for malicious intent. My software is provided for educational purposes only.</strong></p>
<p>The Authentication System can easily manage multiple products, tiers, and users completely server-side. In addition, it includes the following features:</p>
<ul>
<li>Rate-limit requests of users</li>
<li>IP-limit each key so that only one (or more) users can access it</li>
<li>Log requests to a Discord Webhook using CMAnalytics</li>
<li>Lock content behind keys so that they can only be accessed by authenticated users</li>
</ul>
<p><strong>All of these features are configurable</strong>, so you can enable, disable, and change them to your liking.</p>
<h1 id="setup-guide">Setup Guide</h1>
<h2 id="1-download-the-authentication-system">1. Download the Authentication System</h2>
<p>You can download from the &quot;releases&quot; section of this repository.</p>
<h2 id="2-import-the-authentication-system-to-your-hosting-provider">2. Import the Authentication System to your hosting provider</h2>
<p>If you have RDP or file-access to your website, you can simply upload the file to some directory in your website&#39;s files, e.g. <code>yoursite.com/auth/</code> (Optionally, you can rename the file to &quot;index.php&quot; so that it is directly accessible from the directory.)</p>
<h2 id="3-configure-the-settings-in-the-file">3. Configure the settings in the file</h2>
<p>Open up the <code>config.json</code> file and configure the settings to your liking. Here is an example configuration. Each setting is pretty self-explanatory.</p>
<pre><code class="lang-json">{
    <span class="hljs-attr">"productName"</span>: <span class="hljs-string">"example"</span>,
    <span class="hljs-attr">"iplimit"</span>: {
        <span class="hljs-attr">"enabled"</span>: <span class="hljs-literal">true</span>,
        <span class="hljs-attr">"ipAddressesPerKey"</span>: <span class="hljs-number">2</span>
    },
    <span class="hljs-attr">"ratelimit"</span>: {
        <span class="hljs-attr">"enabled"</span>: <span class="hljs-literal">true</span>,
        <span class="hljs-attr">"maxRequestsPerPeriod"</span>: <span class="hljs-number">5</span>,
        <span class="hljs-attr">"timePeriodSeconds"</span>: <span class="hljs-number">15</span>
    },
    <span class="hljs-attr">"logging"</span>: {
        <span class="hljs-attr">"enabled"</span>: <span class="hljs-literal">true</span>,
        <span class="hljs-attr">"webhook_url"</span>: <span class="hljs-string">"https://discord.com/api/webhooks/..."</span>,
        <span class="hljs-attr">"embed_username"</span>: <span class="hljs-string">"PhantomAuth"</span>,
        <span class="hljs-attr">"embed_color_hex"</span>: <span class="hljs-string">"#f44444"</span>,
        <span class="hljs-attr">"embed_avatar_url"</span>: <span class="hljs-string">"https://raw.githubusercontent.com/owengregson/PhantomAuth/main/resources/PhantomAuth.png"</span>
    }
}
</code></pre>
<h2 id="4-execute-the-script-by-accessing-the-page">4. Execute the script by accessing the page</h2>
<p>Visit the website with no parameters in your request, e.g.
<code>https://yoursite.com/auth/</code></p>
<p>Upon your first execution of the script, it will automatically propagate the necessary files and directories for your configured product name.</p>
<p>The automatically generated file structure will look something like the following:</p>
<pre><code>├── (productName)
│   ├── data
│   │   └── example-key<span class="hljs-selector-class">.ip</span>
│   ├── keys
│   │   └── example-keys<span class="hljs-selector-class">.txt</span>
│   └── resources
│       └── example-resource<span class="hljs-selector-class">.txt</span>
└── index.php
</code></pre><h2 id="5-put-in-your-license-keys-and-make-new-tiers-if-you-want-">5. Put in your license keys and make new tiers (if you want.)</h2>
<p>Access the <code>./keys</code> directory and rename the file to whatever you want your access tier to be, e.g. &quot;premium-keys.txt&quot; instead of &quot;example-keys.txt&quot;
Then, simply add the license keys you want into that file, and they will be automatically recognized by the program. You can create new files in this format to add more tiers to your product.</p>
<h2 id="6-all-finished-">6. All finished!</h2>
<p>You can easily change the &quot;productName&quot; in the config.json to make new products and follow the setup guide inside each one once again. Any products you created previously will still authenticate just like before.
Now that you&#39;re finished with setup, you can move on to usage of the authentication system.</p>
<h1 id="usage-guide">Usage Guide</h1>
<h2 id="1-sending-requests">1. Sending Requests</h2>
<p>Each request should be a GET request over HTTP or HTTPS. The URL parameters are formatted as the following:</p>
<ul>
<li>key: The key to attempt authentication with.</li>
<li>type: The product &#39;tier&#39; or type to check the key in. Must have a matching <code>(type)-keys.txt</code> file in the <code>./keys</code> directory.</li>
<li>product: The product to authenticate in.</li>
<li>request: (optional) The locked resource to access.</li>
</ul>
<p>An example authentication request using these parameters would be:</p>
<pre><code>https://yoursite.com/auth/?<span class="hljs-built_in">key</span>=<span class="hljs-built_in">example</span>-<span class="hljs-built_in">key</span>&amp;type=<span class="hljs-built_in">example</span>&amp;<span class="hljs-built_in">product</span>=<span class="hljs-built_in">example</span>&amp;request=<span class="hljs-built_in">example</span>-resource
</code></pre><h2 id="2-receiving-responses">2. Receiving Responses</h2>
<p>Reponses are in the format of a JSON encoded object. The JSON object contains the following properties:</p>
<ul>
<li>product: The product that was authenticated.</li>
<li>type: The product &#39;tier&#39; or type that was checked.</li>
<li>status: Either &quot;valid&quot; or &quot;invalid&quot; indicating the result of the authentication request.</li>
<li>reason: The reason why the authentication failed (or &quot;authorized&quot; if it was successful.)</li>
<li>response: The requested locked data from the request property or &quot;success&quot; if none was provided (and authentication was successful.) Can also be &quot;The request type did not match any resource.&quot; if the script cannot find the requested resource.</li>
</ul>
<p>An example <strong>(successful)</strong> authentication response would be:</p>
<pre><code class="lang-json">{
    <span class="hljs-attr">"product"</span>: <span class="hljs-string">"example"</span>,
    <span class="hljs-attr">"type"</span>: <span class="hljs-string">"example"</span>,
    <span class="hljs-attr">"status"</span>: <span class="hljs-string">"valid"</span>,
    <span class="hljs-attr">"reason"</span>: <span class="hljs-string">"authorized"</span>,
    <span class="hljs-attr">"response"</span>: <span class="hljs-string">"example-resource"</span>
}
</code></pre>
<p>And an example <strong>(unsuccessful)</strong> authentication response is:</p>
<pre><code class="lang-json">{
    <span class="hljs-attr">"product"</span>: <span class="hljs-string">"example"</span>,
    <span class="hljs-attr">"type"</span>: <span class="hljs-string">"example"</span>,
    <span class="hljs-attr">"status"</span>: <span class="hljs-string">"invalid"</span>,
    <span class="hljs-attr">"reason"</span>: <span class="hljs-string">"bad-request"</span>
}
</code></pre>
