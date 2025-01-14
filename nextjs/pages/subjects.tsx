import React, { useEffect, useState } from "react";
import { NextPage, NextPageContext } from "next";
import { useCookies } from "react-cookie";
import styles from "../styles/App.module.css";
import axios from "axios";
import { parseCookies, resolveApiHost } from "../helpers/";
import { useRouter } from "next/router";
import Layout from "../components/layout";

interface Subject {
  id: number;
  name: string;
  test_chamber?: number;
  date_of_birth?: string;
  score?: number;
  alive?: boolean;
  created_at?: string;
  updated_at?: string;
}

Subjects.getInitialProps = ({ req, res }: NextPageContext) => {
  const cookies = parseCookies(req);
  const { protocol, hostname } = resolveApiHost(req);
  return { XSRF_TOKEN: cookies["XSRF-TOKEN"], hostname, protocol };
};

export default function Subjects(
  props: NextPage & { XSRF_TOKEN: string; hostname: string; protocol: string }
) {
  const router = useRouter();
  const [authenticated, setAuth] = useState<Boolean>(!!props.XSRF_TOKEN);
  const [subjects, setSubjects] = useState<Array<Subject>>();
  const [message, setErrorMessage] = useState<string>("");
  const [cookie, setCookie, removeCookie] = useCookies(["XSRF-TOKEN"]);
  const [sortingQuery, setSortingQuery] = useState<string>("");
  const api = `${props.protocol}//${props.hostname}`;

  const logout = async () => {
    try {
      await axios({
        method: "post",
        url: `${api}/logout`,
        withCredentials: true,
      }).then((response) => {
        removeCookie("XSRF-TOKEN");
        setAuth(!(response.status === 204));
        return router.push("/");
      });
    } catch (e) {
      console.log(e);
    }
  };

  const sortAge = () => {
    try {
      setSortingQuery("(orderBy: [{column: DATE_OF_BIRTH, order:ASC}])");
    } catch (e) {
      console.log(e);
    }
  };

  const sortTestChamber = () => {
    try {
      setSortingQuery("(orderBy: [{column: TEST_CHAMBER, order:ASC}])");
    } catch (e) {
      console.log(e);
    }
  };

  const notSorted = () => {
    try {
      setSortingQuery("");
    } catch (e) {
      console.log(e);
    }
  };

  const formatDate = (dateStr: string | undefined) => {
    if (!dateStr) {
      return "???";
    }
    const date = new Date(dateStr);
    return `${date.getDate()}/${date.getMonth() + 1}/${date.getFullYear()}`;
  };

  useEffect(() => {
    if (authenticated) {
      axios
        .post(
          `${api}/graphql`,
          {
            query: `
              query {
                subjects ${sortingQuery} {
                  id
                  name
                  test_chamber
                  date_of_birth
                  score
                  alive
                  created_at
                }
              }
            `,
          },
          { withCredentials: true }
        )
        .then((response) => {
          const { subjects = [] } = response.data?.data;
          if (subjects && subjects.length > 0) {
            return setSubjects(subjects as Subject[]);
          }
        })
        .catch((e) => {
          console.log(e);
          if (e.response?.data?.message) {
            if (e.response?.data?.message === "CSRF token mismatch.") {
              return setErrorMessage(
                "Your session has expired, please log in again."
              );
            } else {
              return setErrorMessage(e.response?.data?.message);
            }
          } else {
            return setErrorMessage(
              "An error occurred, please try again later."
            );
          }
        });
    } else {
      router.push("/");
      return;
    }
  }, [authenticated, sortingQuery]);

  return (
    <Layout>
      <h1>Testing Subjects</h1>
      <section className={styles.content}>
        {message && <p data-testid="error-msg">{message}</p>}
        {subjects && subjects.length > 0 && (
          <table data-testid="subjects-table">
            <thead>
              <tr>
                <td>ID</td>
                <td>Name</td>
                <td>DOB</td>
                <td>Alive</td>
                <td>Score</td>
                <td>Test Chamber</td>
              </tr>
            </thead>
            <tbody>
              {subjects.map((subject) => (
                <tr key={subject.id}>
                  <td>{subject.id}</td>
                  <td>{subject.name}</td>
                  <td>{formatDate(subject.date_of_birth)}</td>
                  <td>{subject.alive ? "Y" : "N"}</td>
                  <td>{subject.score}</td>
                  <td>{subject.test_chamber}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
        {!subjects && !message && (
          <div className={styles.skeleton} data-testid="skeleton">
            <table>
              <thead>
                <tr>
                  <td>ID</td>
                  <td>Name</td>
                  <td>DOB</td>
                  <td>Alive</td>
                  <td>Score</td>
                  <td>Test Chamber</td>
                </tr>
              </thead>
              <tbody>
                {Array.from(Array(10).keys()).map((subject) => (
                  <tr key={subject}>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
        {authenticated && <button onClick={logout}>Log out</button>}
        <button onClick={sortAge}>Sort By Age</button>
        <button onClick={sortTestChamber}>Sort By Test Chamber</button>
        <button onClick={notSorted}>Sort By Id</button>
      </section>
    </Layout>
  );
}
