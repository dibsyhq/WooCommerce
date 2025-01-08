(()=>{"use strict";const M=window.React,N=window.wc.wcBlocksRegistry;var D=window.wc.wcSettings.getPaymentMethodData("dibsy-v2");"yes"===D?.enabled&&(0,N.registerPaymentMethod)({name:"dibsy-v2",label:(0,M.createElement)("div",null,D.title,(0,M.createElement)("div",{className:"dibsy-icons"},(0,M.createElement)("img",{src:"data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iNjRweCIgaGVpZ2h0PSI0MHB4IiB2aWV3Qm94PSIwIDAgNjQgNDAiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayI+CiAgICA8IS0tIEdlbmVyYXRvcjogU2tldGNoIDU4ICg4NDY2MykgLSBodHRwczovL3NrZXRjaC5jb20gLS0+CiAgICA8dGl0bGU+U1JDX2ljb25fYmxhY2s8L3RpdGxlPgogICAgPGRlc2M+Q3JlYXRlZCB3aXRoIFNrZXRjaC48L2Rlc2M+CiAgICA8ZGVmcz4KICAgICAgICA8cG9seWdvbiBpZD0icGF0aC0xIiBwb2ludHM9IjAgMCA2NCAwIDY0IDQwIDAgNDAiPjwvcG9seWdvbj4KICAgIDwvZGVmcz4KICAgIDxnIGlkPSJQYWdlLTEiIHN0cm9rZT0ibm9uZSIgc3Ryb2tlLXdpZHRoPSIxIiBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPgogICAgICAgIDxnIGlkPSJJY29uLW9ubHkiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0yMDguMDAwMDAwLCAtMjYxLjAwMDAwMCkiPgogICAgICAgICAgICA8ZyBpZD0iU1JDX2ljb25fYmxhY2siIHRyYW5zZm9ybT0idHJhbnNsYXRlKDIwOC4wMDAwMDAsIDI2MS4wMDAwMDApIj4KICAgICAgICAgICAgICAgIDxtYXNrIGlkPSJtYXNrLTIiIGZpbGw9IndoaXRlIj4KICAgICAgICAgICAgICAgICAgICA8dXNlIHhsaW5rOmhyZWY9IiNwYXRoLTEiPjwvdXNlPgogICAgICAgICAgICAgICAgPC9tYXNrPgogICAgICAgICAgICAgICAgPGcgaWQ9InBhdGgtMSI+PC9nPgogICAgICAgICAgICAgICAgPHBhdGggZD0iTTYzLjQ3NDUwMzIsMTguNzIxNTI2MyBMNDguMTQwNDgyNywwLjkxNTA1ODk3NCBDNDcuODE3MTE4OSwwLjM4NzE4NzIyNCA0Ny4yMzI0NjMzLDAuMDM0NjIyNTM1MSA0Ni41NjUxMjY4LDAuMDM0NjIyNTM1MSBDNDUuNTQ3NDM4NSwwLjAzNDYyMjUzNTEgNDQuNzIyMzQ1NSwwLjg1NDM0NzYyNyA0NC43MjIzNDU1LDEuODY1OTU5NTkgQzQ0LjcyMjM0NTUsMi4zOTg5NTE1NyA0NC45NTE3NDI1LDIuODc4NzkwNjUgNDUuMzE3NTUwOCwzLjIxMzU1NjQzIEw1OS4yMDg5NDY3LDE5Ljk0OTQwNzIgTDQ1LjQ3MTg3MjQsMzYuMzY3MzE1OCBMNDMuOTc0MDQ1MywzNi4zNjczMTU4IEwzOS41Mzc0ODM4LDM2LjM2NzMxNTggTDUyLjUxOTYzMzYsMjEuMzU1NTIxIEM1Mi41Mjc5NzUzLDIxLjM0NjI1NTggNTIuNTM1NTgxLDIxLjMzNjk5MDYgNTIuNTQzNjc3MywyMS4zMjc3MjU1IEw1Mi41NDYzNzYxLDIxLjMyODcwMDcgQzUyLjkwODAxMzcsMjAuOTMzNzExMyA1My4wNjg5NTk1LDIwLjQ3MjE1ODcgNTMuMDYwMzcyNSwyMC4wMTcxODk0IEM1My4wNjg5NTk1LDE5LjU2MjQ2MzggNTIuOTA4MDEzNywxOS4xMDA5MTEzIDUyLjU0NjM3NjEsMTguNzA1OTIxOCBMNTIuNTQzNjc3MywxOC43MDY2NTMzIEM1Mi41MzU1ODEsMTguNjk3Mzg4MSA1Mi41Mjc5NzUzLDE4LjY4ODEyMjkgNTIuNTE5NjMzNiwxOC42NzkxMDE1IEwzNy4wMTczMDY5LDAuOTQ0MDczNjM0IEMzNy4wMTM4NzIxLDAuOTM5OTI4NjgyIDM3LjAxMDQzNzIsMC45MzU3ODM3MzEgMzcuMDA2NzU3MSwwLjkzMTYzODc4IEMzNi42ODU4NDY3LDAuMzk0NTAxODQ0IDM2LjA5NjAzODksMC4wMzQ2MjI1MzUxIDM1LjQyMjA3ODEsMC4wMzQ2MjI1MzUxIEwyNC41MzQ0MzI4LDAuMDM0NjIyNTM1MSBDMjMuNTE2NzQ0NSwwLjAzNDYyMjUzNTEgMjIuNjkxNjUxNiwwLjg1NDM0NzYyNyAyMi42OTE2NTE2LDEuODY1OTU5NTkgQzIyLjY5MTY1MTYsMi4xNzI5Mjk4MSAyMi43MzM4NTA4LDIuNDkyMDkxMDcgMjIuOTAyNjQ3NywyLjcxNjY0OTkgTDM3LjMzMTEwMjMsMjAuMDg1MjE1MyBMMjUuMDMzOTUzOSwzNC43NDY4ODM3IEMyNC45NjA4NDEzLDM0LjgxOTU0MjIgMjMuOTAxMTk5MSwzNi4zMjAyNTg0IDIyLjAzODA1NDMsMzYuMzIwMjU4NCBMNy4zNTE0OTc0NSwzNi4zMjAyNTg0IEM1LjM1NDE0OTI3LDM2LjMyMDI1ODQgMy42OTc4Mjk3MSwzNC43NzEyNjU3IDMuNjk3ODI5NzEsMzIuNzEzNjYzMSBMMy42OTc4Mjk3MSw3LjI5ODA0MDI5IEMzLjY5NzgyOTcxLDUuNTA2OTMzNjUgNS4zNTQxNDkyNywzLjY2NzMwNjcgNy4zOTY2NDA4LDMuNjY3MzA2NyBMMTYuNTMzNTA4OSwzLjY2NzMwNjcgQzE3LjYxMDU3MDUsMy42NjczMDY3IDE4LjM3NjI5MDIsMi45MzYzMzIzMyAxOC4zNzYyOTAyLDEuODY1OTU5NTkgQzE4LjM3NjI5MDIsMC43OTU1ODY4NDYgMTcuNjEwNTcwNSwwIDE2LjUzMzUwODksMCBMNy4zMDcwOTAxMiwwLjAzNjMyOTI3OTggQzIuOTM2MjgwOTIsMC4wMzYzMjkyNzk4IDAsMy42MTg3ODYzOCAwLDcuMjYwNzM1NzMgTDAsMzIuNzEzNjYzMSBDMCwzNi42MzUwMzA5IDMuNDc4NzM3MjMsMzkuOTc1MTMwMyA3LjMwNzA5MDEyLDM5Ljk3NTEzMDMgTDIxLjk4OTQ3NjEsMzkuOTc1MTMwMyBDMjIuNzI5OTI1MywzOS45NzUxMzAzIDIzLjQwNzMyMSwzOS44NjczNjE2IDI0LjAxNzI0NywzOS42OTQ3MzY1IEMyNC4xMDQzNDQyLDM5LjY3MDExMDYgMjQuMTkwMjE0NywzOS42NDQwMjE4IDI0LjI3NDM2NzgsMzkuNjE2OTU3NyBDMjYuMjk4NzAzOSwzOC45NjQ5ODEzIDI3LjUyMDUxODYsMzcuNjI1NDMwNSAyNy43NTcwMzA2LDM3LjM0MTYyMzIgTDQxLjU5NjQxMzQsMjEuMzEyODUyNCBDNDIuMjUwOTkyMSwyMC41NzQ4MDcyIDQyLjMzODA4OTMsMTkuNTA0NDM0NSA0MS42MDcyMDg2LDE4LjcwNTkyMTggTDQxLjYwNDUwOTgsMTguNzA2NjUzMyBDNDEuNTk2NDEzNCwxOC42OTczODgxIDQxLjU4ODgwNzcsMTguNjg4MTIyOSA0MS41ODA3MTE0LDE4LjY3OTEwMTUgTDI4LjQ4OTg3NDEsMy42NjczMDY3IEwzMy4wMzUxMjMxLDMuNjY3MzA2NyBMMzQuNTMyOTUwMiwzLjY2NzMwNjcgTDQ4LjIxMzM0OTksMjAuMDE3NDMzMiBMMzMuODE3MjgwOCwzNy4yNzQ4MTY0IEMzMy44MTI2MTkzLDM3LjI3OTkzNjYgMzMuODIxNDUxNywzNy4yOTQ4MDk3IDMzLjg0MTgxNTIsMzcuMzE3OTcyNiBDMzMuNzA3MzY2NSwzNy41NzIyNzc2IDMzLjYzMDgxOTEsMzcuODYxNDQ4OSAzMy42MzA4MTkxLDM4LjE2ODY2MjkgQzMzLjYzMDgxOTEsMzkuMTgwMDMxMSAzNC40NTU5MTIxLDQwIDM1LjQ3MzM1NSw0MCBMNDYuMzYxMDAwMyw0MCBDNDcuMDM1MjA2NSw0MCA0Ny42MjQ1MjM2LDM5LjYzOTYzMyA0Ny45NDU5MjQ2LDM5LjEwMjczOTkgQzQ3Ljk0OTM1OTUsMzkuMDk4NTk1IDQ3Ljk1Mjc5NDMsMzkuMDk0NDUgNDcuOTU2MjI5MSwzOS4wOTA1NDg5IEw2My40NTg4MDEyLDIxLjM1NTUyMSBDNjMuNDY2ODk3NSwyMS4zNDYyNTU4IDYzLjQ3NDUwMzIsMjEuMzM2OTkwNiA2My40ODI1OTk2LDIxLjMyNzcyNTUgTDYzLjQ4NTI5ODMsMjEuMzI4NzAwNyBDNjQuMjE1OTMzNywyMC41Mjk5NDQyIDY0LjEyOTA4MTksMTkuNDU5NTcxNSA2My40NzQ1MDMyLDE4LjcyMTUyNjMiIGlkPSJGaWxsLTEiIGZpbGw9IiMwMDAwMDAiIGZpbGwtcnVsZT0ibm9uemVybyIgbWFzaz0idXJsKCNtYXNrLTIpIj48L3BhdGg+CiAgICAgICAgICAgIDwvZz4KICAgICAgICA8L2c+CiAgICA8L2c+Cjwvc3ZnPg==",alt:"Click To Pay Icon"}),(0,M.createElement)("img",{src:"data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iMjQiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTM2LjY2NyAwSDMuMzMzQzEuNDkzIDAgMCAxLjQ2NSAwIDMuMjczdjE3LjQ1NEMwIDIyLjUzNSAxLjQ5MiAyNCAzLjMzMyAyNGgzMy4zMzRDMzguNTA3IDI0IDQwIDIyLjUzNSA0MCAyMC43MjdWMy4yNzNDNDAgMS40NjUgMzguNTA4IDAgMzYuNjY3IDB6IiBmaWxsPSIjMjI0REJBIi8+PHBhdGggZD0iTTEyLjIyMiAxNi4zNjNoLTIuMzFMOC4xNzcgOS41MzRhMS4wOCAxLjA4IDAgMDAtLjUxMS0uNzIgNy4wOTggNy4wOTggMCAwMC0yLjExMS0uNzJ2LS4yNjFoMy43MjJjLjI0NS4wMDcuNDc4LjEwMi42NTYuMjY3YS45NzUuOTc1IDAgMDEuMzEuNjI3bC44OSA0Ljg4NyAyLjMxLTUuNzgxaDIuMjIzbC0zLjQ0NSA4LjUzem00Ljc0NSAwaC0yLjIyMmwxLjc4OC04LjQ3NmgyLjIyM2wtMS43OSA4LjQ3NnptNC42MjItNi4xM2EuODI2LjgyNiAwIDAxLjMwNy0uNTMuODU1Ljg1NSAwIDAxLjU5My0uMTggMy45MjkgMy45MjkgMCAwMTIuMTExLjM4MmwuMzc4LTEuODIxYTUuMzgyIDUuMzgyIDAgMDAtMS45ODktLjQ0OGMtMi4xMTEgMC0zLjY1NiAxLjE3OC0zLjY1NiAyLjgwNC4wNi41MTUuMjY4IDEuMDA0LjU5OCAxLjQwOS4zMy40MDUuNzcuNzEgMS4yNy44ODIuODMyLjM4MiAxLjExLjY1NCAxLjExIDEuMDM2YTEuMDkgMS4wOSAwIDAxLS40NjMuNjhjLS4yMzkuMTU4LS41My4yMi0uODE1LjE3MWE1LjMyNCA1LjMyNCAwIDAxLTIuMjIyLS41MjRsLS4zNzggMS44MzNjLjc1LjMwNiAxLjU1NS40NTkgMi4zNjcuNDQ3IDIuMzc4LjA2NiAzLjg1Ni0xLjA5IDMuODU2LTIuODY5IDAtMi4xODItMy4wMjMtMi4zNDUtMy4wMjMtMy4zMjdsLS4wNDQuMDU1em0xMC42MzMgNi4xM0wzMC41IDcuODMzaC0xLjg1NWEuOTkuOTkgMCAwMC0uNTU1LjE4OC45NjEuOTYxIDAgMDAtLjM0NS40NjZsLTMuMiA3Ljg3NmgyLjIyMmwuNDQ0LTEuMjQzaDIuNzlsLjI0NCAxLjI0M2gxLjk3N3ptLTMuMjU1LTYuMjVsLjYzMyAzLjE5NmgtMS44MjJsMS4xODktMy4xOTZ6IiBmaWxsPSIjZmZmIi8+PC9zdmc+DQo=",alt:"Visa Icon"}),(0,M.createElement)("img",{src:"data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iMjQiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTM2LjY2NyAwSDMuMzMzQzEuNDkzIDAgMCAxLjQ2NSAwIDMuMjczdjE3LjQ1NEMwIDIyLjUzNSAxLjQ5MiAyNCAzLjMzMyAyNGgzMy4zMzRDMzguNTA3IDI0IDQwIDIyLjUzNSA0MCAyMC43MjdWMy4yNzNDNDAgMS40NjUgMzguNTA4IDAgMzYuNjY3IDB6IiBmaWxsPSIjMDAxMjJDIi8+PHBhdGggZD0iTTE1LjU1NiAxOC41NDZjMy42ODIgMCA2LjY2Ni0yLjkzMSA2LjY2Ni02LjU0NnMtMi45ODUtNi41NDUtNi42NjYtNi41NDVjLTMuNjgyIDAtNi42NjcgMi45My02LjY2NyA2LjU0NSAwIDMuNjE1IDIuOTg1IDYuNTQ2IDYuNjY3IDYuNTQ2eiIgZmlsbD0iI0U4MkMzMCIvPjxwYXRoIGQ9Ik0yNC40NDUgMTguNTQ2YzMuNjgxIDAgNi42NjYtMi45MzEgNi42NjYtNi41NDZzLTIuOTg1LTYuNTQ1LTYuNjY2LTYuNTQ1Yy0zLjY4MiAwLTYuNjY3IDIuOTMtNi42NjcgNi41NDUgMCAzLjYxNSAyLjk4NSA2LjU0NiA2LjY2NyA2LjU0NnoiIGZpbGw9IiNFQ0EyMUQiLz48cGF0aCBkPSJNMjAgNi41NDVhNi4yNDggNi4yNDggMCAwMC0yLjMyIDIuMDg2IDYuMDc3IDYuMDc3IDAgMDAtLjQzMiA1Ljk2NyA2LjIgNi4yIDAgMDAxLjk5NiAyLjM4N2wuNzU2LjQ3aC4wNzhhNi4yNCA2LjI0IDAgMDAyLjQyNS0yLjI5IDYuMDkgNi4wOSAwIDAwLjg3OC0zLjE4OCA2LjA5MSA2LjA5MSAwIDAwLS45MjMtMy4xNzdBNi4yNDUgNi4yNDUgMCAwMDIwIDYuNTQ1eiIgZmlsbD0iI0YzNkQxRSIvPjwvc3ZnPg0K",alt:"MasterCard Icon"}),(0,M.createElement)("img",{src:"data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iMjQiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTM2LjY2NyAwSDMuMzMzQzEuNDkzIDAgMCAxLjQ2NSAwIDMuMjczdjE3LjQ1NEMwIDIyLjUzNSAxLjQ5MiAyNCAzLjMzMyAyNGgzMy4zMzRDMzguNTA3IDI0IDQwIDIyLjUzNSA0MCAyMC43MjdWMy4yNzNDNDAgMS40NjUgMzguNTA4IDAgMzYuNjY3IDB6IiBmaWxsPSIjMDA2RkNGIi8+PHBhdGggZD0iTTI1LjU1NSAxMy4wOWgzdi42OTlIMjYuNDl2LjYxaDIuMDF2LjYyM2gtMi4wMXYuNjQzaDIuMDY2di42OThoLTN2LTMuMjcyem04Ljc3OCAxLjI3N2MxLjExMSAwIDEuMjIyLjU0NSAxLjIyMiAxLjA5YTEuMDg2IDEuMDg2IDAgMDEtLjM4NS42NTUgMS4xMjQgMS4xMjQgMCAwMS0uNzI2LjI1MWgtMS45Nzd2LS43MDloMS41Yy4yNTUgMCAuNjMzIDAgLjYzMy0uMzA1IDAtLjMwNi0uMDY3LS4yNTEtLjMzMy0uMjczaC0uNjM0Yy0xLjAxIDAtMS4yNTUtLjQ1OC0xLjI1NS0xLjAwNGEuOTYxLjk2MSAwIDAxLjI3My0uNjgyLjk5Ny45OTcgMCAwMS42ODItLjNoMi4wMjJ2LjY5OWgtMS4zNzdjLS4zMjMgMC0uNjY3IDAtLjY2Ny4yODQgMCAuMjgzLjE2Ny4yNC4zODkuMjVoLjY0NGwtLjAxLjA0NHptLTMuNDQ0IDBjMS4xMSAwIDEuMjIyLjU0NSAxLjIyMiAxLjA5YTEuMDg1IDEuMDg1IDAgMDEtLjM3Mi42ODJjLS4yMDUuMTc1LS40NjguMjctLjc0LjI2OGgtMS45Nzd2LS43MWgxLjVjLjI1NiAwIC42MzMgMCAuNjMzLS4zMDQgMC0uMzA2LS4wNjYtLjI1MS0uMzMzLS4yNzNoLS42MzNjLTEuMDExIDAtMS4yNTYtLjQ1OC0xLjI1Ni0xLjAwNGEuOTYzLjk2MyAwIDAxLjI5OC0uNjcyLjk5OS45OTkgMCAwMS42OTEtLjI3N2gyLjAyMnYuNjk4aC0xLjRjLS4zMjIgMC0uNjY2IDAtLjY2Ni4yODRzLjE2Ni4yNC4zODkuMjVoLjYzM2wtLjAxMS0uMDMyem0tMTAuODktMS4yNzZoLTMuMTY2bC0xLjA0NCAxLjAxNC0xLjAyMi0xLjAxNGgtMy42djMuMjcyaDMuNDg4bDEuMTEyLTEuMDkgMS4xMSAxLjA5aDEuNzU2di0xLjA5aDEuMjM0Yy4yNzUuMDQuNTU2LS4wMjMuNzg2LS4xNzcuMjMtLjE1My4zOTQtLjM4Ni40NTgtLjY1M2ExLjE1NSAxLjE1NSAwIDAwMC0uMzM4Ljk4Mi45ODIgMCAwMC0uMjYtLjcgMS4wMTggMS4wMTggMCAwMC0uNjg0LS4zMjVsLS4xNjcuMDF6bS01Ljg2NiAyLjU3NGgtMi4xMXYtLjY0M2gyLjAxVjE0LjRoLTEuOTY2di0uNjExaDIuMjIybC44ODkuODgzLTEuMDQ1Ljk5M3ptMy41LjM3MWwtMS4zMjItMS4zNTMgMS4zMjItMS4yNzZ2Mi42M3ptMi4wNTYtMS40NjJoLTEuMTExdi0uNzg1aDEuMTFhLjQxOC40MTggMCAwMS4zMS4wNTQuMzk4LjM5OCAwIDAxLjEyNi41NTUuNDExLjQxMSAwIDAxLS4yNTcuMTc2LjM3My4zNzMgMCAwMS0uMTkgMGguMDEyem00LjczMy4yOTVhLjg5OS44OTkgMCAwMC40NzctLjM0NS44NzIuODcyIDAgMDAuMTU2LS41Ni45NDMuOTQzIDAgMDAtLjMxNy0uNjQxLjk3Ny45NzcgMCAwMC0uNjgzLS4yNDNoLTIuNDMzdjMuMjcyaC45MTF2LTEuMTQ1aDEuMmEuNDg2LjQ4NiAwIDAxLjMzNy4xNjguNDY1LjQ2NSAwIDAxLjEwOC4zNTZ2LjU4OWguODg5di0uNjY2YS42MzMuNjMzIDAgMDAtLjI0Mi0uNjg4LjY2MS42NjEgMCAwMC0uMjM2LS4xMDhoLS4xMTFsLS4wNTYuMDF6bS0uNzg5LS4zMzhoLTEuMTF2LS43NDJoMS4xMWEuNDMuNDMgMCAwMS40MzcuMTc2LjQxLjQxIDAgMDEuMDYzLjE1MXYuMDY2YzAgLjIxOC0uMTMzLjM2LS41LjM2di0uMDExek0yMy4yNjcgMTJWOC43MjdoLS45MjNWMTJoLjkyM3ptLTguNDktMy4yNzNoM3YuNjk4SDE1Ljd2LjYyMmgyLjA3OHYuNjFIMTUuN3YuNjQ1aDIuMDc4VjEyaC0zVjguNzI3em02LjQ0NSAxLjc3OGEuODg5Ljg4OSAwIDAwLjQ4LS4zNDIuODYxLjg2MSAwIDAwLjE1My0uNTYzLjkxNC45MTQgMCAwMC0uMzE5LS42NDUuOTUuOTUgMCAwMC0uNjkyLS4yMjhoLTIuNVYxMmguOTIzdi0xLjE1N2gxLjIxYS41MTQuNTE0IDAgMDEuMzQxLjE3LjQ5Ni40OTYgMCAwMS4xMjYuMzU0di42aC45di0uNjY1YS42NDMuNjQzIDAgMDAtLjIzMy0uNjkzLjY3LjY3IDAgMDAtLjIzMy0uMTE1aC0uMTM0bC0uMDIyLjAxMXptLS44LS4zMzhoLTEuMTExdi0uNzQyaDEuMTExYS40NC40NCAwIDAxLjMxNy4wNjUuNDI2LjQyNiAwIDAxLjE4My4yNjJ2LjA2NmMwIC4yMTgtLjEzMy4zNi0uNTExLjM2bC4wMTEtLjAxMXptLTcuNzc4LTEuNDRsLTEuMTEgMi4xODItMS4xMTItMi4xODJIOC44OXYzLjE0Mkw3LjMxIDguNzI3SDYuMDg5TDQuNDQ0IDEyaC45NzhsLjM1Ni0uNzMxaDEuODQ0bC4zNjcuNzNoMS44NTVWOS41NzlMMTEuMTExIDEyaC44MzNsMS4yNjctMi4zNzhWMTJoLjkyMlY4LjcyN2gtMS40ODl6bS02LjUxIDEuODMzbC41NTUtMS4wOTEuNTc4IDEuMDlINi4xMzN6TTMyLjgxIDguNzI3djIuMjU4bC0xLjU1Ni0yLjI1OEgyOS44OXYzLjA2NkwyOC4zMSA4LjcyN2gtMS4yMjJsLTEuMjc4IDIuNTNoLS41ODlhLjY4Ni42ODYgMCAwMS0uNDQ4LS4yNTguNjYuNjYgMCAwMS0uMTMtLjQ5NHYtLjI1YzAtLjc2NC40NzgtLjgxOSAxLjExMS0uODE5aC41Nzh2LS43MDlIMjUuMWMtLjM5NC4wNzMtLjc1LjI4Mi0xLjAwMS41ODhhMS42MzkgMS42MzkgMCAwMC4wNTggMi4xNWMuMjY4LjI5My42MzQuNDgyIDEuMDMyLjUzNWgxLjIyMmwuMzY3LS43MzFoMS44NDRsLjM1Ni43M2gxLjgzM1Y5LjYxMkwzMi40NzggMTJoMS4yNTVWOC43MjdoLS45MjJ6bS01LjY3OCAxLjgzM2wuNTU2LTEuMDkxLjU3OCAxLjA5aC0xLjEzNHoiIGZpbGw9IiNmZmYiLz48L3N2Zz4NCg==",alt:"Amex Icon"}))),content:(0,M.createElement)("div",null,D.description),edit:null,icons:null,canMakePayment:()=>!0,ariaLabel:"Dibsy Checkout",supports:{features:void 0}}),(0,N.registerExpressPaymentMethod)({name:"dibsy-v2-apple-pay",content:void 0!==(window.ApplePaySession&&ApplePaySession.canMakePayments())?(0,M.createElement)("apple-pay-button",{buttonstyle:"black",type:"plain",locale:"en",style:{width:"100%"}}):null,edit:null,canMakePayment:()=>void 0!==(window.ApplePaySession&&ApplePaySession.canMakePayments()),supports:{features:void 0}})})();