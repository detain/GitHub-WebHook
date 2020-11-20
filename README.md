This script acts as a web hook for [GitHub](https://github.com/) events, processes them,
and returns messages which can be sent out to an IRC channel.

## GitHubWebHook
`GitHubWebHook.php` accepts, processes and validates an event,
it also can make sure that the event came from a GitHub server.

Functions in this class are:

#### ProcessRequest()
Accepts an event, throws `Exception` on error.

#### GetEventType()
Returns event type.
See https://developer.github.com/webhooks/#events for a list of events.

#### GetPayload()
Returns decoded JSON payload as an object.

#### GetFullRepositoryName()
Returns full name of the repository for which an event was sent for.

#### ValidateHubSignature( $SecretKey )
Retuns true if HMAC hex digest of the payload matches GitHub's, false otherwise.

#### ~~ValidateIPAddress()~~
Returns true if a request came from GitHub's IP range, false otherwise.
⚠ Use `ValidateHubSignature` instead.

## IrcConverter
`IrcConverter.php` accepts input from previous script and outputs
a colored string which can be sent to IRC.

#### __construct( $EventType, $Payload, $URLShortener = null )
`IrcConverter` constructor takes 3 paramaters (last one is optional).
All you need to do is pass data after parsing the message with `GitHubWebHook`
like so: `new IrcConverter( $Hook->GetEventType(), $Hook->GetPayload() );`

URL shortener paramater takes a function, and that function should accept
a single string argument containing an url. If your function fails to
shorten an url or do anything with it, your function must return the
original url back.

#### GetMessage()
After calling the constructor, using this function will return
a string which can be sent to an IRC server.

Throws `NotImplementedException` when you pass an event that
is not parsed anyhow, and throws `IgnoredEventException` for
`fork`, `watch` and `status` events which are ignored by design.

## Events [\[ref\]](https://developer.github.com/v3/activity/events/types/)

Event                         | Status | Notes
----------------------------- | ------ | -----
CommitCommentEvent            | :+1: |
CreateEvent                   | :x: |
DeleteEvent                   | :+1: |
DeploymentEvent               | :x: |
DeploymentStatusEvent         | :x: |
ForkEvent                     | :droplet: | Ignored by design
GollumEvent                   | :+1: |
InstallationEvent             | :x: |
InstallationRepositoriesEvent | :x: |
IssueCommentEvent             | :+1: |
IssuesEvent                   | :+1: | `assigned`, `unassigned`, `labeled`, `unlabeled` events are ignored by design
LabelEvent                    | :x: |
MarketplacePurchaseEvent      | :x: |
MemberEvent                   | :+1: | `edited` events are ignored by design
MembershipEvent               | :x: |
MilestoneEvent                | :+1: | `edited` events are ignored by design
OrganizationEvent             | :x: |
OrgBlockEvent                 | :x: |
PackageEvent                  | :+1: |
PageBuildEvent                | :x: |
PingEvent                     | :+1: | Not documented by GitHub, sent out when a new hook is created
ProjectCardEvent              | :x: |
ProjectColumnEvent            | :x: |
ProjectEvent                  | :+1: | `edited` events are ignored by design
PublicEvent                   | :+1: |
PullRequestEvent              | :+1: | `synchronize`, `assigned`, `unassigned`, `labeled`, `unlabeled`, `review_requested`, `review_request_removed` events are ignored by design
PullRequestReviewEvent        | :+1: |
PullRequestReviewCommentEvent | :+1: |
PushEvent                     | :+1: | Only distinct commits are counted and printed. Ignores branch deletions (use `delete` event instead)
ReleaseEvent                  | :+1: |
RepositoryEvent               | :+1: |
StatusEvent                   | :droplet: | Ignored by design
TeamEvent                     | :x: |
TeamAddEvent                  | :x: |
WatchEvent                    | :droplet: | Ignored by design
RepositoryVulnerabilityAlertEvent | :+1: |

## License
[MIT](LICENSE)
